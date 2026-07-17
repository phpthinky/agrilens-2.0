<?php

namespace App\Http\Controllers;

use App\Models\Crop;
use App\Models\Farm;
use App\Models\SoilSample;
use App\Services\FertilizerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnalysisController extends Controller
{
    public function __construct(private readonly FertilizerService $fertilizer)
    {
    }

    public function chooseType(Farm $farm)
    {
        $this->authorise($farm);
        return view('analyses.choose', compact('farm'));
    }

    public function createManual(Farm $farm)
    {
        $this->authorise($farm);
        return view('analyses.manual', compact('farm'));
    }

    public function createProbe(Farm $farm)
    {
        $this->authorise($farm);
        return view('analyses.probe', compact('farm'));
    }

    // Handles both Manual and Colorimetric creation — they share the same
    // entry point and only diverge in what happens after the row is created.
    public function store(Request $request, Farm $farm)
    {
        $this->authorise($farm);
        if ($blocked = $this->checkSampleLimit()) {
            return $blocked;
        }

        $request->validate([
            'analysis_type' => 'required|in:manual,colorimetric',
            'sample_name' => 'required|string|max:150',
            'sample_date' => 'required|date|before_or_equal:today',
            'date_tested' => 'required|date|before_or_equal:today|after_or_equal:sample_date',
        ]);

        $base = [
            'farm_id' => $farm->id,
            'farmer_id' => $farm->farmer_id,
            'analysis_type' => $request->analysis_type,
            'sample_name' => $request->sample_name,
            'farmer_name' => $farm->farmer->full_name,
            'address' => $farm->farmer->address,
            'sample_date' => $request->sample_date,
            'date_tested' => $request->date_tested,
            'color_hex' => '#8B4513',
        ];

        if ($request->analysis_type === 'manual') {
            $request->validate([
                'soil_type' => 'nullable|string|max:100',
                'ph_level' => 'required|numeric|between:0,14',
                'nitrogen_level' => 'required|numeric|min:0',
                'phosphorus_level' => 'required|numeric|min:0',
                'potassium_level' => 'required|numeric|min:0',
            ]);

            $ph = (float) $request->ph_level;
            $n = (float) $request->nitrogen_level;
            $p = (float) $request->phosphorus_level;
            $k = (float) $request->potassium_level;

            $sample = Auth::user()->soilSamples()->create($base + [
                'soil_type' => $request->soil_type,
                'ph_level' => $ph,
                'nitrogen_level' => $n,
                'phosphorus_level' => $p,
                'potassium_level' => $k,
                'fertility_score' => $this->fertilizer->computeFertilityScore($ph, $n, $p, $k),
                'recommended_crop' => Crop::topMatchName($ph, $n, $p, $k),
                'analyzed_at' => now(),
            ]);

            return redirect()->route('samples.show', $sample)
                ->with('success', 'Manual soil analysis recorded successfully.');
        }

        $sample = Auth::user()->soilSamples()->create($base);

        return redirect()->route('samples.show', $sample)
            ->with('success', 'Soil sample added successfully! Ready for webcam analysis.');
    }

    public function storeProbe(Request $request, Farm $farm)
    {
        $this->authorise($farm);
        if ($blocked = $this->checkSampleLimit()) {
            return $blocked;
        }

        $request->validate([
            'sample_name' => 'required|string|max:150',
            'sample_date' => 'required|date|before_or_equal:today',
            'date_tested' => 'required|date|before_or_equal:today|after_or_equal:sample_date',
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

        $sample = Auth::user()->soilSamples()->create([
            'farm_id' => $farm->id,
            'farmer_id' => $farm->farmer_id,
            'analysis_type' => 'probe',
            'sample_name' => $request->sample_name,
            'farmer_name' => $farm->farmer->full_name,
            'address' => $farm->farmer->address,
            'sample_date' => $request->sample_date,
            'date_tested' => $request->date_tested,
            'color_hex' => '#8B4513',
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

    private function authorise(Farm $farm): void
    {
        if (!Auth::user()->getAccessibleFarms()->whereKey($farm->id)->exists()) {
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
