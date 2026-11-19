<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Crop;
use App\Models\Farm;
use App\Models\NpkColorChart;
use App\Models\PhColorChart;
use App\Models\PhTest;
use App\Models\SoilSample;
use App\Services\FertilizerService;
use App\Services\PhTestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Mobile colorimetric soil testing.
 *
 * Unlike ColorimetricController (the app uploads each photo and the server
 * reads it), here the app does the whole BSWM workflow itself and offline:
 * it downloads the colour charts once (config()), picks the colour from each
 * webcam photo, matches it against those charts, runs the pH CPR → BCG/BTB
 * decision and the 3-capture averaging, then uploads the finished test with
 * its photos in one request (store()). The server keeps the phone's values,
 * so the website shows exactly what the technician saw in the field.
 */
class MobileColorimetricController extends Controller
{
    private const NUTRIENTS = ['nitrogen', 'phosphorus', 'potassium'];

    /** Matches the reaction countdown on the web N/P/K and pH pages. */
    private const REACTION_SECONDS = 480;

    public function __construct(
        private readonly FertilizerService $fertilizer,
        private readonly PhTestService $phTestService,
    ) {
    }

    /**
     * The active colour charts the app needs to read photos offline.
     * `version` changes whenever any chart row is added, edited, deactivated
     * or removed, so the app knows when its saved copy is out of date.
     */
    public function config(): JsonResponse
    {
        $ph = PhColorChart::where('active', true)->orderBy('indicator')->orderBy('ph_value')->orderBy('id')
            ->get(['id', 'indicator', 'ph_value', 'hex_value', 'updated_at']);
        $npk = NpkColorChart::where('active', true)->orderBy('nutrient')->orderBy('ppm_value')->orderBy('id')
            ->get(['id', 'nutrient', 'ppm_value', 'hex_value', 'updated_at']);

        $phCharts = [];
        foreach (array_keys(PhColorChart::INDICATORS) as $indicator) {
            $phCharts[$indicator] = $ph->where('indicator', $indicator)->values()
                ->map(fn ($row) => ['hex' => strtoupper($row->hex_value), 'value' => (float) $row->ph_value])
                ->all();
        }

        $npkCharts = [];
        foreach (array_keys(NpkColorChart::NAMES) as $nutrient) {
            $npkCharts[$nutrient] = $npk->where('nutrient', $nutrient)->values()
                ->map(fn ($row) => ['hex' => strtoupper($row->hex_value), 'value' => (float) $row->ppm_value])
                ->all();
        }

        $updatedAt = collect([$ph->max('updated_at'), $npk->max('updated_at')])->filter()->max();

        return response()->json([
            'version'          => sha1(json_encode([$phCharts, $npkCharts])),
            'updated_at'       => $updatedAt?->toIso8601String(),
            'reaction_seconds' => self::REACTION_SECONDS,
            'ph_charts'        => $phCharts,
            'npk_charts'       => $npkCharts,
        ]);
    }

    /**
     * Save a finished mobile colorimetric test: multipart with `payload`
     * (JSON, see validatePayload()) and one `photos[<name>]` file per capture.
     * Re-sending the same client_uuid returns the sample already created.
     */
    public function store(Request $request, Farm $farm): JsonResponse
    {
        $user = Auth::user();
        abort_unless(
            $user->isAdmin() || in_array($farm->location_barangay_id, $user->getAccessibleBarangayIds()),
            403
        );

        $request->validate([
            'payload'  => 'required|json',
            'photos'   => 'nullable|array|max:15',
            'photos.*' => 'file|mimes:jpeg,jpg,png|max:10240',
        ]);

        $data = $this->validatePayload(json_decode($request->input('payload'), true) ?? []);

        $existing = SoilSample::where('client_uuid', $data['client_uuid'])->first();
        if ($existing) {
            abort_unless($existing->user_id === $user->id, 409, 'This test was already uploaded by another account.');

            return response()->json($this->result($existing, 'This test was already uploaded.'));
        }

        /** @var array<string, UploadedFile> $photos */
        $photos = $request->file('photos', []);
        $farm->loadMissing('farmer');

        $createdId = null;
        try {
            $sample = DB::transaction(function () use ($user, $farm, $data, $photos, &$createdId) {
                $ph = (float) $data['levels']['ph'];
                $n  = (float) $data['levels']['nitrogen'];
                $p  = (float) $data['levels']['phosphorus'];
                $k  = (float) $data['levels']['potassium'];

                $sample = $user->soilSamples()->create([
                    'farm_id'          => $farm->id,
                    'farmer_id'        => $farm->farmer_id,
                    'analysis_type'    => 'mobile_colorimetric',
                    'client_uuid'      => $data['client_uuid'],
                    'sample_name'      => $data['sample_name'] ?? ('Mobile Colorimetric — ' . now()->format('M j, Y')),
                    'farmer_name'      => $farm->farmer->full_name,
                    'address'          => $farm->farmer->address,
                    'sample_date'      => now()->toDateString(),
                    'date_tested'      => now()->toDateString(),
                    'notes'            => $data['notes'] ?? null,
                    'color_hex'        => '#8B4513',
                    'ph_level'         => $ph,
                    'nitrogen_level'   => $n,
                    'phosphorus_level' => $p,
                    'potassium_level'  => $k,
                    'fertility_score'  => $this->fertilizer->computeFertilityScore($ph, $n, $p, $k),
                    'recommended_crop' => Crop::topMatchName($ph, $n, $p, $k),
                    'analyzed_at'      => now(),
                ]);
                $createdId = $sample->id;

                $save = fn (array $reading) => $this->savePhoto($photos[$reading['photo'] ?? ''] ?? null, $sample->id, $reading['photo'] ?? '');

                // pH: the two-step BSWM record, plus its final step as 3 colour readings.
                $step1 = array_map(fn ($rd) => $this->phReading($rd, $save($rd)), $data['ph']['step1']);
                $step2 = array_map(fn ($rd) => $this->phReading($rd, $save($rd)), $data['ph']['step2'] ?? []);
                $this->savePhTest($sample, $data['ph'], $step1, $step2);

                $final = $step2 ?: $step1;
                $this->saveReadings($sample->id, 'ph', array_map(fn ($rd) => [
                    'hex' => $rd['hex'], 'r' => $rd['r'], 'g' => $rd['g'], 'b' => $rd['b'],
                    'computed_value' => $rd['computed_value'], 'image' => $rd['image'],
                ], $final));

                $columns = ['ph_color_hex' => $this->phTestService->averageHex($final)];

                foreach (self::NUTRIENTS as $nutrient) {
                    $readings = array_map(fn ($rd) => [
                        'hex'            => strtoupper($rd['hex']),
                        'r'              => (int) $rd['r'],
                        'g'              => (int) $rd['g'],
                        'b'              => (int) $rd['b'],
                        'computed_value' => (float) $rd['computed_value'],
                        'image'          => $save($rd),
                    ], $data['nutrients'][$nutrient]);

                    $this->saveReadings($sample->id, $nutrient, $readings);
                    $columns["{$nutrient}_color_hex"] = $this->phTestService->averageHex($readings);
                }

                $columns['tests_completed'] = DB::table('soil_color_readings')->where('sample_id', $sample->id)->count();
                $sample->update($columns);

                return $sample;
            });
        } catch (Throwable $e) {
            // The transaction rolled back the rows; don't leave its photos behind.
            if ($createdId !== null) {
                File::deleteDirectory(public_path("captures/{$createdId}"));
            }
            throw $e;
        }

        return response()->json($this->result($sample, 'Mobile colorimetric test saved.'), 201);
    }

    // ─────────────────────────────────────────────────────────────

    /**
     * payload = {
     *   client_uuid, sample_name?, notes?, config_version?,
     *   levels:    {ph, nitrogen, phosphorus, potassium},
     *   ph:        {step1: [reading×3], step2_solution: BCG|BTB|null, step2: [reading×3]|[], final_ph},
     *   nutrients: {nitrogen: [reading×3], phosphorus: [reading×3], potassium: [reading×3]},
     * }
     * reading = {photo, hex, r, g, b, computed_value, confidence_pct?, chart_ph?, zone?}
     */
    private function validatePayload(array $payload): array
    {
        $reading = fn (string $prefix) => [
            "{$prefix}.photo"          => 'required|string|max:40|regex:/^[a-z0-9-]+$/',
            "{$prefix}.hex"            => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            "{$prefix}.r"              => 'required|integer|between:0,255',
            "{$prefix}.g"              => 'required|integer|between:0,255',
            "{$prefix}.b"              => 'required|integer|between:0,255',
            "{$prefix}.computed_value" => 'required|numeric|min:0',
            "{$prefix}.confidence_pct" => 'nullable|integer|between:0,100',
            "{$prefix}.chart_ph"       => 'nullable|numeric|between:0,14',
            "{$prefix}.zone"           => 'nullable|string|in:tube,center',
        ];

        $rules = [
            'client_uuid'         => 'required|uuid',
            'sample_name'         => 'nullable|string|max:150',
            'notes'               => 'nullable|string|max:2000',
            'config_version'      => 'nullable|string|max:64',
            'levels.ph'           => 'required|numeric|between:0,14',
            'levels.nitrogen'     => 'required|numeric|min:0',
            'levels.phosphorus'   => 'required|numeric|min:0',
            'levels.potassium'    => 'required|numeric|min:0',
            'ph.step1'            => 'required|array|size:3',
            'ph.step2_solution'   => 'nullable|in:BCG,BTB',
            'ph.step2'            => 'nullable|array',
            'ph.final_ph'         => 'required|numeric|between:0,14',
        ] + $reading('ph.step1.*') + $reading('ph.step2.*');

        foreach (self::NUTRIENTS as $nutrient) {
            $rules["nutrients.{$nutrient}"] = 'required|array|size:3';
            $rules += $reading("nutrients.{$nutrient}.*");
        }

        $data = Validator::make($payload, $rules)->validate();

        $step2Count = count($data['ph']['step2'] ?? []);
        if (!empty($data['ph']['step2_solution']) && $step2Count !== 3) {
            throw ValidationException::withMessages(['ph.step2' => 'pH Step 2 needs exactly 3 captures.']);
        }
        if (empty($data['ph']['step2_solution']) && $step2Count !== 0) {
            throw ValidationException::withMessages(['ph.step2' => 'pH Step 2 captures were sent without a Step 2 indicator.']);
        }

        return $data;
    }

    private function phReading(array $rd, ?string $image): array
    {
        return [
            'hex'            => strtoupper($rd['hex']),
            'r'              => (int) $rd['r'],
            'g'              => (int) $rd['g'],
            'b'              => (int) $rd['b'],
            'computed_value' => (float) $rd['computed_value'],
            'confidence_pct' => isset($rd['confidence_pct']) ? (int) $rd['confidence_pct'] : null,
            'image'          => $image,
            'chart_ph'       => isset($rd['chart_ph']) ? (float) $rd['chart_ph'] : (float) $rd['computed_value'],
        ];
    }

    /** Same PhTest record the web pH page builds, from the phone's readings. */
    private function savePhTest(SoilSample $sample, array $ph, array $step1, array $step2): void
    {
        $stats1 = $this->phTestService->computeStats(array_column($step1, 'chart_ph'));
        $next   = $this->phTestService->decideSolution($stats1['average']);
        $remark = $this->phTestService->generateStep1Remarks($stats1['average'], $next, $stats1['confidence']);

        $attributes = [
            'sample_id'        => $sample->id,
            'step1_readings'   => $step1,
            'step1_ph'         => $stats1['average'],
            'step1_chart_ph'   => $this->snap($stats1['average'], 'CPR'),
            'step1_variance'   => $stats1['variance'],
            'step1_confidence' => $stats1['confidence'],
            'step1_outcome'    => $remark['outcome'],
            'step1_remarks'    => $remark['remarks'],
            'next_solution'    => $next,
            'final_ph'         => (float) $ph['final_ph'],
            'status'           => 'complete',
        ];

        if ($step2) {
            $solution = $ph['step2_solution'];
            $stats2   = $this->phTestService->computeStats(array_column($step2, 'chart_ph'));
            $remark2  = $this->phTestService->generateStep2Remarks($stats1['average'], $stats2['average'], $solution, $stats2['confidence']);

            $attributes += [
                'step2_solution'   => $solution,
                'step2_readings'   => $step2,
                'step2_ph'         => $stats2['average'],
                'step2_chart_ph'   => $this->snap($stats2['average'], $solution),
                'step2_variance'   => $stats2['variance'],
                'step2_confidence' => $stats2['confidence'],
                'step2_outcome'    => $remark2['outcome'],
                'step2_remarks'    => $remark2['remarks'],
            ];
        }

        PhTest::create($attributes);
    }

    /** Nearest card value at or above $ph, as the web pH page stores it; the raw value if the chart is empty. */
    private function snap(float $ph, string $solution): float
    {
        try {
            return PhTestService::snapToChartPh($ph, $solution);
        } catch (Throwable) {
            return $ph;
        }
    }

    private function saveReadings(int $sampleId, string $parameter, array $readings): void
    {
        foreach (array_values($readings) as $i => $rd) {
            DB::table('soil_color_readings')->insert([
                'sample_id'      => $sampleId,
                'parameter'      => $parameter,
                'test_number'    => $i + 1,
                'color_hex'      => $rd['hex'],
                'captured_image' => $rd['image'] ?? null,
                'r'              => $rd['r'],
                'g'              => $rd['g'],
                'b'              => $rd['b'],
                'computed_value' => $rd['computed_value'],
                'captured_at'    => now(),
            ]);
        }
    }

    /** Store under public/captures/{id}/ like the web capture pages (no storage symlink needed). */
    private function savePhoto(?UploadedFile $photo, int $sampleId, string $name): ?string
    {
        if (!$photo || !$photo->isValid() || !preg_match('/^[a-z0-9-]+$/', $name)) {
            return null;
        }

        $dir = public_path("captures/{$sampleId}");
        File::ensureDirectoryExists($dir, 0755);
        $file = "{$name}.jpg";
        $photo->move($dir, $file);

        return "captures/{$sampleId}/{$file}";
    }

    private function result(SoilSample $sample, string $message): array
    {
        return [
            'message'          => $message,
            'sample_id'        => $sample->id,
            'fertility_score'  => $sample->fertility_score,
            'recommended_crop' => $sample->recommended_crop,
        ];
    }
}
