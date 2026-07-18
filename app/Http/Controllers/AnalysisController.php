<?php

namespace App\Http\Controllers;

use App\Models\Crop;
use App\Models\Farm;
use App\Models\NpkColorChart;
use App\Models\PhColorChart;
use App\Models\SoilSample;
use App\Services\ColorScienceService;
use App\Services\FertilizerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnalysisController extends Controller
{
    public function __construct(
        private readonly FertilizerService $fertilizer,
        private readonly ColorScienceService $colorScience,
    ) {
    }

    // ── Step 1: Create Sample ────────────────────────────────────────

    public function createSample(Farm $farm)
    {
        $this->authoriseFarm($farm);
        return view('samples.create', compact('farm'));
    }

    public function storeSample(Request $request, Farm $farm)
    {
        $this->authoriseFarm($farm);
        if ($blocked = $this->checkSampleLimit()) {
            return $blocked;
        }

        $request->validate([
            'sample_name' => 'required|string|max:150',
            'sample_date' => 'required|date|before_or_equal:today',
            'date_tested' => 'required|date|before_or_equal:today|after_or_equal:sample_date',
            'notes' => 'nullable|string|max:2000',
        ]);

        $sample = Auth::user()->soilSamples()->create([
            'farm_id' => $farm->id,
            'farmer_id' => $farm->farmer_id,
            'analysis_type' => 'pending',
            'sample_name' => $request->sample_name,
            'farmer_name' => $farm->farmer->full_name,
            'address' => $farm->farmer->address,
            'sample_date' => $request->sample_date,
            'date_tested' => $request->date_tested,
            'notes' => $request->notes,
            'color_hex' => '#8B4513',
        ]);

        return redirect()->route('analyses.choose', $sample)
            ->with('success', 'Sample created. Choose an analysis method to continue.');
    }

    // ── Step 2: Select Analysis Type ─────────────────────────────────

    public function chooseType(SoilSample $sample)
    {
        $this->authoriseSample($sample);
        return view('analyses.choose', compact('sample'));
    }

    public function startColorimetric(SoilSample $sample)
    {
        $this->authoriseSample($sample);
        $sample->update(['analysis_type' => 'colorimetric']);

        return redirect()->route('samples.show', $sample)
            ->with('success', 'Ready for webcam analysis.');
    }

    // ── Manual Entry (value or reagent-color selection per parameter) ─

    public function createManual(SoilSample $sample)
    {
        $this->authoriseSample($sample);

        // Same "DB chart if calibrated, else hardcoded reference" fallback that
        // ColorScienceService itself uses when resolving a captured/selected color —
        // the swatches shown here must match what resolution will actually use.
        $charts = [
            'ph' => [
                'CPR' => PhColorChart::chartForIndicator('CPR') ?: ColorScienceService::CPR_COLOR_CHART,
                'BCG' => PhColorChart::chartForIndicator('BCG') ?: ColorScienceService::BCG_COLOR_CHART,
                'BTB' => PhColorChart::chartForIndicator('BTB') ?: ColorScienceService::BTB_COLOR_CHART,
            ],
            'nitrogen' => NpkColorChart::chartForNutrient('N') ?: ColorScienceService::NITROGEN_COLOR_CHART,
            'phosphorus' => NpkColorChart::chartForNutrient('P') ?: ColorScienceService::PHOSPHORUS_COLOR_CHART,
            'potassium' => NpkColorChart::chartForNutrient('K') ?: ColorScienceService::POTASSIUM_COLOR_CHART,
        ];

        return view('analyses.manual', compact('sample', 'charts'));
    }

    public function storeManual(Request $request, SoilSample $sample)
    {
        $this->authoriseSample($sample);

        $request->validate([
            'soil_type' => 'nullable|string|max:100',
            'ph_mode' => 'required|in:value,color',
            'ph_value' => 'required_if:ph_mode,value|nullable|numeric|between:0,14',
            'ph_indicator' => 'required_if:ph_mode,color|nullable|in:CPR,BCG,BTB',
            'ph_hex' => 'required_if:ph_mode,color|nullable|string',
            'nitrogen_mode' => 'required|in:value,color',
            'nitrogen_value' => 'required_if:nitrogen_mode,value|nullable|numeric|min:0',
            'nitrogen_hex' => 'required_if:nitrogen_mode,color|nullable|string',
            'phosphorus_mode' => 'required|in:value,color',
            'phosphorus_value' => 'required_if:phosphorus_mode,value|nullable|numeric|min:0',
            'phosphorus_hex' => 'required_if:phosphorus_mode,color|nullable|string',
            'potassium_mode' => 'required|in:value,color',
            'potassium_value' => 'required_if:potassium_mode,value|nullable|numeric|min:0',
            'potassium_hex' => 'required_if:potassium_mode,color|nullable|string',
        ]);

        [$ph, $phHex] = $this->resolvePh($request);
        [$n, $nHex] = $this->resolveNutrient($request, 'nitrogen');
        [$p, $pHex] = $this->resolveNutrient($request, 'phosphorus');
        [$k, $kHex] = $this->resolveNutrient($request, 'potassium');

        $sample->update([
            'analysis_type' => 'manual',
            'soil_type' => $request->soil_type,
            'ph_level' => $ph,
            'nitrogen_level' => $n,
            'phosphorus_level' => $p,
            'potassium_level' => $k,
            'ph_color_hex' => $phHex,
            'nitrogen_color_hex' => $nHex,
            'phosphorus_color_hex' => $pHex,
            'potassium_color_hex' => $kHex,
            'fertility_score' => $this->fertilizer->computeFertilityScore($ph, $n, $p, $k),
            'recommended_crop' => Crop::topMatchName($ph, $n, $p, $k),
            'analyzed_at' => now(),
        ]);

        return redirect()->route('samples.show', $sample)
            ->with('success', 'Manual soil analysis recorded successfully.');
    }

    /** @return array{0: float, 1: ?string} [ph_value, ph_color_hex] */
    private function resolvePh(Request $request): array
    {
        if ($request->ph_mode === 'color') {
            $result = $this->colorScience->phTestColorToPhLevel($request->ph_hex, $request->ph_indicator);
            return [$result['ph'], $request->ph_hex];
        }

        return [(float) $request->ph_value, null];
    }

    /** @return array{0: float, 1: ?string} [ppm_value, color_hex] */
    private function resolveNutrient(Request $request, string $parameter): array
    {
        $mode = $request->input("{$parameter}_mode");

        if ($mode === 'color') {
            $hex = $request->input("{$parameter}_hex");
            $value = $this->colorScience->computeForParameter($parameter, $hex);
            return [$value, $hex];
        }

        return [(float) $request->input("{$parameter}_value"), null];
    }

    // ── Digital Probe ─────────────────────────────────────────────────

    public function createProbe(SoilSample $sample)
    {
        $this->authoriseSample($sample);
        return view('analyses.probe', compact('sample'));
    }

    public function storeProbe(Request $request, SoilSample $sample)
    {
        $this->authoriseSample($sample);

        $request->validate([
            'probe_id' => 'required|string|max:100',
            'ph_level' => 'required|numeric|between:0,14',
            'nitrogen_level' => 'required|numeric|min:0',
            'phosphorus_level' => 'required|numeric|min:0',
            'potassium_level' => 'required|numeric|min:0',
            'probe_raw_payload' => 'nullable|string',
        ]);

        $ph = (float) $request->ph_level;
        $n = (float) $request->nitrogen_level;
        $p = (float) $request->phosphorus_level;
        $k = (float) $request->potassium_level;

        $rawPayload = $request->probe_raw_payload ? json_decode($request->probe_raw_payload, true) : null;

        $sample->update([
            'analysis_type' => 'probe',
            'probe_id' => $request->probe_id,
            'probe_raw_payload' => $rawPayload,
            'ph_level' => $ph,
            'nitrogen_level' => $n,
            'phosphorus_level' => $p,
            'potassium_level' => $k,
            'fertility_score' => $this->fertilizer->computeFertilityScore($ph, $n, $p, $k),
            'recommended_crop' => Crop::topMatchName($ph, $n, $p, $k),
            'analyzed_at' => now(),
        ]);

        return redirect()->route('samples.show', $sample)
            ->with('success', "Analysis from probe {$request->probe_id} saved successfully.");
    }

    // ── Authorisation ─────────────────────────────────────────────────

    private function authoriseFarm(Farm $farm): void
    {
        if (!Auth::user()->getAccessibleFarms()->whereKey($farm->id)->exists()) {
            abort(403);
        }
    }

    private function authoriseSample(SoilSample $sample): void
    {
        $user = Auth::user();
        if (!$user->isAdmin() && $sample->user_id !== $user->id) {
            abort(403);
        }
    }

    private function checkSampleLimit()
    {
        if (Auth::user()->isAdmin()) {
            return null;
        }

        $limit = 5000;
        if (Auth::user()->soilSamples()->count() >= $limit) {
            return redirect()->route('samples.index')
                ->with('error', "Maximum limit of {$limit} samples reached. Please settle the unpaid modification fee to continue using the system.");
        }

        return null;
    }
}
