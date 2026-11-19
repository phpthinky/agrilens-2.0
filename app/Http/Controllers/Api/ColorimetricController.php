<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ColorReadingController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PhTestController;
use App\Models\Farm;
use App\Models\SoilSample;
use App\Services\PhTestService;
use App\Services\SnapshotColorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Colorimetric soil testing from the field app.
 *
 * The app is only a camera: it drives the colorimeter box's USB/OTG webcam
 * (which mobile browsers can't open) and uploads each photo here. Picking the
 * color, converting it to pH / ppm, the 3-capture averaging and the pH
 * CPR → BCG/BTB decision all run through the same code the web pages use, so
 * the server remains the source of truth. The finished result is shown by
 * opening the web sample page inside the app (see resultLink()).
 */
class ColorimetricController extends Controller
{
    private const NUTRIENTS = ['nitrogen', 'phosphorus', 'potassium'];

    private const LABELS = [
        'ph'         => 'pH',
        'nitrogen'   => 'Nitrogen (N)',
        'phosphorus' => 'Phosphorus (P)',
        'potassium'  => 'Potassium (K)',
    ];

    private const REAGENTS = [
        'nitrogen'   => 'Nitrogen Reagent (N-Reagent)',
        'phosphorus' => 'Phosphorus Reagent (P-Reagent)',
        'potassium'  => 'Potassium Reagent (K-Reagent)',
    ];

    /** Matches the reaction countdown on the web N/P/K and pH Step 1 pages. */
    private const REACTION_SECONDS = 480;

    public function __construct(
        private readonly SnapshotColorService $snapshotColor,
        private readonly PhTestService $phTestService,
    ) {
    }

    /** Create a colorimetric sample on a farm (web: Create Sample + "Colorimetric"). */
    public function store(Request $request, Farm $farm): JsonResponse
    {
        $user = Auth::user();
        abort_unless(
            $user->isAdmin() || in_array($farm->location_barangay_id, $user->getAccessibleBarangayIds()),
            403
        );

        $validated = $request->validate([
            'sample_name' => 'nullable|string|max:150',
            'notes'       => 'nullable|string|max:2000',
        ]);

        $farm->loadMissing('farmer');

        $sample = $user->soilSamples()->create([
            'farm_id'       => $farm->id,
            'farmer_id'     => $farm->farmer_id,
            'analysis_type' => 'colorimetric',
            'sample_name'   => $validated['sample_name']
                ?? ('Field Colorimetric — ' . now()->format('M j, Y')),
            'farmer_name'   => $farm->farmer->full_name,
            'address'       => $farm->farmer->address,
            'sample_date'   => now()->toDateString(),
            'date_tested'   => now()->toDateString(),
            'notes'         => $validated['notes'] ?? null,
            'color_hex'     => '#8B4513',
        ]);

        return response()->json($this->status($sample), 201);
    }

    public function show(SoilSample $sample): JsonResponse
    {
        $this->authoriseSample($sample);

        return response()->json($this->status($sample));
    }

    /** Upload one webcam photo for a parameter; the server picks the color and records the reading. */
    public function capture(Request $request, SoilSample $sample): JsonResponse
    {
        $this->authoriseSample($sample);

        $validated = $request->validate([
            'parameter' => 'required|in:ph,nitrogen,phosphorus,potassium',
            'photo'     => 'required|file|mimes:jpeg,jpg,png|max:10240',
        ]);

        if ($sample->isAnalyzed()) {
            return response()->json(['message' => 'This sample has already been analyzed.'], 422);
        }

        $photo = $request->file('photo');
        $bytes = file_get_contents($photo->getRealPath());

        try {
            $color = $this->snapshotColor->sampleTube($bytes);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $payload = [
            'sample_id' => $sample->id,
            'color_hex' => $color['hex'],
            'r'         => $color['r'],
            'g'         => $color['g'],
            'b'         => $color['b'],
            'snapshot'  => 'data:' . $photo->getMimeType() . ';base64,' . base64_encode($bytes),
        ];

        $parameter = $validated['parameter'];

        if ($parameter === 'ph') {
            $step     = $sample->phTest?->status === 'step2' ? 2 : 1;
            $response = $this->forward(
                fn (Request $sub) => app(PhTestController::class)->capture($sub),
                $payload + ['step' => $step]
            );
        } else {
            $taken      = DB::table('soil_color_readings')
                ->where('sample_id', $sample->id)
                ->where('parameter', $parameter)
                ->pluck('test_number')
                ->all();
            $testNumber = collect([1, 2, 3])->first(fn (int $n) => !in_array($n, $taken));

            if ($testNumber === null) {
                return response()->json(['message' => self::LABELS[$parameter] . ' already has 3 captures.'], 422);
            }

            $response = $this->forward(
                fn (Request $sub) => app(ColorReadingController::class)->store($sub),
                $payload + ['parameter' => $parameter, 'test_number' => $testNumber]
            );
        }

        $result = $response->getData(true);

        if ($response->getStatusCode() !== 200 || empty($result['success'])) {
            return response()->json(
                ['message' => $result['message'] ?? 'The capture could not be saved.'],
                $response->getStatusCode() === 200 ? 422 : $response->getStatusCode()
            );
        }

        return response()->json([
            'message' => $result['message'] ?? (self::LABELS[$parameter] . ' capture saved.'),
            'capture' => [
                'parameter'      => $parameter,
                'color_hex'      => $color['hex'],
                'zone'           => $color['method'],
                'computed_value' => $result['computed_value'] ?? null,
            ],
            'status'  => $this->status($sample),
        ]);
    }

    /** Throw away a parameter's captures so it can be retaken (e.g. a pH "Retest Required"). */
    public function reset(SoilSample $sample, string $parameter): JsonResponse
    {
        $this->authoriseSample($sample);
        abort_unless(array_key_exists($parameter, self::LABELS), 404);

        if ($sample->isAnalyzed()) {
            return response()->json(['message' => 'This sample has already been analyzed.'], 422);
        }

        if ($parameter === 'ph') {
            app(PhTestController::class)->reset($sample);
        } else {
            $query = DB::table('soil_color_readings')
                ->where('sample_id', $sample->id)
                ->where('parameter', $parameter);

            foreach ($query->clone()->pluck('captured_image') as $path) {
                if ($path && is_file(public_path($path))) {
                    @unlink(public_path($path));
                }
            }
            $query->delete();

            $sample->update([
                "{$parameter}_color_hex" => null,
                'tests_completed'        => DB::table('soil_color_readings')
                    ->where('sample_id', $sample->id)->count(),
            ]);
        }

        return response()->json([
            'message' => self::LABELS[$parameter] . ' captures cleared.',
            'status'  => $this->status($sample),
        ]);
    }

    /**
     * A short-lived, single-use link that signs the app's in-app browser into
     * the web app and lands on the sample page — whose first visit runs the
     * analysis once all four averaged colors exist (SampleController::show).
     */
    public function resultLink(SoilSample $sample): JsonResponse
    {
        $this->authoriseSample($sample);

        if (!$sample->allAveraged() && !$sample->isAnalyzed()) {
            return response()->json(['message' => 'Finish all pH, N, P and K captures before analyzing.'], 422);
        }

        $nonce = Str::random(48);
        Cache::put("mobile-handoff:{$nonce}", Auth::id(), now()->addMinutes(5));

        return response()->json([
            'url' => URL::temporarySignedRoute(
                'mobile.handoff',
                now()->addMinutes(5),
                ['sample' => $sample->id, 'nonce' => $nonce]
            ),
        ]);
    }

    // ─────────────────────────────────────────────────────────────

    /**
     * Run a web colorimeter JSON endpoint with a synthetic request, so app
     * captures go through exactly the same validation, conversion and
     * averaging as browser captures.
     */
    private function forward(callable $action, array $payload): JsonResponse
    {
        $sub = Request::create('/', 'POST', $payload);
        $sub->headers->set('Accept', 'application/json');

        return $action($sub);
    }

    private function status(SoilSample $sample): array
    {
        $sample->refresh()->load('phTest', 'farm');

        $counts   = DB::table('soil_color_readings')
            ->where('sample_id', $sample->id)
            ->selectRaw('parameter, COUNT(*) as cnt')
            ->groupBy('parameter')
            ->pluck('cnt', 'parameter');
        $analyzed = $sample->isAnalyzed();

        $parameters = [$this->phStatus($sample, $analyzed)];

        foreach (self::NUTRIENTS as $nutrient) {
            $captures = (int) ($counts[$nutrient] ?? 0);
            $avgHex   = $sample->{"{$nutrient}_color_hex"};

            $parameters[] = [
                'key'              => $nutrient,
                'label'            => self::LABELS[$nutrient],
                'reagent'          => self::REAGENTS[$nutrient],
                'captures'         => $captures,
                'required'         => 3,
                'complete'         => (bool) $avgHex,
                'can_capture'      => !$analyzed && $captures < 3,
                'can_reset'        => !$analyzed && $captures > 0,
                'reaction_seconds' => self::REACTION_SECONDS,
                'average_hex'      => $avgHex,
                'message'          => null,
            ];
        }

        return [
            'sample' => [
                'id'               => $sample->id,
                'sample_name'      => $sample->sample_name,
                'farm_name'        => $sample->farm?->farm_name,
                'farmer_name'      => $sample->farmer_name,
                'analyzed'         => $analyzed,
                'ready_to_analyze' => $analyzed || $sample->allAveraged(),
            ],
            'parameters' => $parameters,
        ];
    }

    private function phStatus(SoilSample $sample, bool $analyzed): array
    {
        $phTest = $sample->phTest;
        $state  = $phTest?->status ?? 'step1';

        [$captures, $indicator, $message] = match ($state) {
            'step2'    => [
                $phTest->step2Count(),
                $phTest->step2_solution,
                "Step 1 (CPR) gave pH {$phTest->step1_ph}. Step 2: test with {$phTest->step2_solution}.",
            ],
            'retest'   => [$phTest->step1Count(), 'CPR', $phTest->step1_remarks ?: 'Retest required — reset pH and test again.'],
            'complete' => [3, $phTest->step2_solution ?: 'CPR', "Final pH {$phTest->final_ph}."],
            default    => [$phTest?->step1Count() ?? 0, 'CPR', null],
        };

        $step = $state === 'step2' ? 2 : 1;

        return [
            'key'              => 'ph',
            'label'            => self::LABELS['ph'],
            'reagent'          => "Step {$step}: {$indicator} indicator",
            'step'             => $step,
            'indicator'        => $indicator,
            'state'            => $state,
            'captures'         => $captures,
            'required'         => 3,
            'complete'         => $state === 'complete' && (bool) $sample->ph_color_hex,
            'can_capture'      => !$analyzed && in_array($state, ['step1', 'step2']) && $captures < 3,
            'can_reset'        => !$analyzed && ($phTest !== null),
            'reaction_seconds' => $step === 2
                ? $this->phTestService->reactionTimer($indicator)
                : self::REACTION_SECONDS,
            'average_hex'      => $sample->ph_color_hex,
            'message'          => $message,
        ];
    }

    private function authoriseSample(SoilSample $sample): void
    {
        $user = Auth::user();
        abort_unless($user->isAdmin() || $sample->user_id === $user->id, 403);
    }
}
