<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Crop;
use App\Models\FertilizerScheduleStage;
use Illuminate\Http\Request;

class FertilizerCalibrationController extends Controller
{
    public function index()
    {
        $crops = Crop::active()
            ->orderBy('name')
            ->withCount('fertilizerScheduleStages')
            ->get();

        return view('admin.fertilizer-calibration.index', compact('crops'));
    }

    public function edit(Crop $crop)
    {
        $stages = $crop->fertilizerScheduleStages()->get()->keyBy('stage');

        return view('admin.fertilizer-calibration.edit', compact('crop', 'stages'));
    }

    public function update(Request $request, Crop $crop)
    {
        $validated = $request->validate([
            'stages' => 'required|array|size:3',
            'stages.*.label' => 'required|string|max:150',
            'stages.*.n_fraction' => 'required|numeric|min:0|max:1',
            'stages.*.p_fraction' => 'required|numeric|min:0|max:1',
            'stages.*.k_fraction' => 'required|numeric|min:0|max:1',
        ]);

        // Each nutrient's 3 stage fractions must sum to 1.0 (fully scheduled)
        // or 0.0 (not scheduled for this crop at all) — anything in between
        // would under- or over-apply that nutrient across the season.
        foreach (['n', 'p', 'k'] as $nutrient) {
            $sum = round(array_sum(array_column($validated['stages'], "{$nutrient}_fraction")), 2);
            if ($sum !== 1.0 && $sum !== 0.0) {
                return back()->withInput()->with(
                    'error',
                    strtoupper($nutrient) . " fractions across the 3 stages must sum to 1.0 (currently {$sum}). Use 0 for all three stages if this nutrient isn't scheduled for this crop."
                );
            }
        }

        foreach ($validated['stages'] as $stage => $data) {
            FertilizerScheduleStage::updateOrCreate(
                ['crop_id' => $crop->id, 'stage' => $stage],
                [
                    'label' => $data['label'],
                    'n_fraction' => $data['n_fraction'],
                    'p_fraction' => $data['p_fraction'],
                    'k_fraction' => $data['k_fraction'],
                ]
            );
        }

        return redirect()->route('admin.fertilizer-calibration.index')
            ->with('success', "Fertilizer schedule calibration saved for {$crop->name}.");
    }

    public function destroy(Crop $crop)
    {
        $crop->fertilizerScheduleStages()->delete();

        return redirect()->route('admin.fertilizer-calibration.index')
            ->with('success', "Fertilizer schedule calibration removed for {$crop->name}.");
    }
}
