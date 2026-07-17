<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Crop;
use App\Models\CropFertilizerSchedule;
use Illuminate\Http\Request;

class CropFertilizerScheduleController extends Controller
{
    public function index()
    {
        $crops = Crop::active()->orderBy('name')
            ->with(['fertilizerSchedules' => fn($q) => $q->orderBy('application_order')->orderBy('fertilizer_type')])
            ->get();

        return view('admin.crop-fertilizer-schedules.index', compact('crops'));
    }

    public function edit(Crop $crop)
    {
        $schedule = CropFertilizerSchedule::where('crop_id', $crop->id)
            ->orderBy('application_order')
            ->orderBy('fertilizer_type')
            ->get();

        $matrix   = CropFertilizerSchedule::matrixForCrop($crop->id);
        $types    = CropFertilizerSchedule::fertilizerTypes();

        return view('admin.crop-fertilizer-schedules.edit', compact('crop', 'schedule', 'matrix', 'types'));
    }

    public function update(Request $request, Crop $crop)
    {
        $types  = CropFertilizerSchedule::fertilizerTypes();
        $labels = [
            1 => 'First Application (0-14 DAT*)',
            2 => 'Second Application (22-26 DAT)',
            3 => 'Third Application (32-36 DAT)',
        ];

        $request->validate([
            'schedule'                  => 'required|array',
            'schedule.*.*'              => 'nullable|numeric|min:0|max:999',
            'label.*'                   => 'nullable|string|max:100',
        ]);

        foreach (range(1, 3) as $order) {
            $label = $request->input("label.{$order}") ?: ($labels[$order] ?? "Application {$order}");

            foreach ($types as $type) {
                $bags = $request->input("schedule.{$order}.{$type}");

                if (!is_null($bags) && $bags !== '') {
                    CropFertilizerSchedule::updateOrCreate(
                        ['crop_id' => $crop->id, 'application_order' => $order, 'fertilizer_type' => $type],
                        ['application_label' => $label, 'bags' => (float)$bags]
                    );
                } else {
                    CropFertilizerSchedule::where([
                        'crop_id'           => $crop->id,
                        'application_order' => $order,
                        'fertilizer_type'   => $type,
                    ])->delete();
                }
            }
        }

        return redirect()->route('admin.crop-fertilizer-schedules.index')
            ->with('success', "Fertilizer schedule updated for {$crop->name}.");
    }
}
