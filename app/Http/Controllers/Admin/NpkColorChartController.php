<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NpkColorChart;
use Illuminate\Http\Request;

class NpkColorChartController extends Controller
{
    public function index()
    {
        $charts = NpkColorChart::orderBy('nutrient')
            ->orderBy('ppm_value')
            ->orderBy('hex_value')
            ->get()
            ->groupBy('nutrient');

        return view('admin.npk-color-charts', compact('charts'));
    }


    public function toggle(NpkColorChart $npkColorChart)
    {
        $npkColorChart->update(['active' => !$npkColorChart->active]);

        $state = $npkColorChart->active ? 'activated' : 'deactivated';

        return redirect()->route('admin.npk-color-charts')
            ->with('success', "Entry {$npkColorChart->hex_value} ({$npkColorChart->nutrient}) {$state}.");
    }

    public function destroy(NpkColorChart $npkColorChart)
    {
        $label = "{$npkColorChart->hex_value} / {$npkColorChart->nutrient} {$npkColorChart->ppm_value} ppm";
        $npkColorChart->delete();

        return redirect()->route('admin.npk-color-charts')
            ->with('success', "Color entry \"{$label}\" deleted permanently.");
    }
}
