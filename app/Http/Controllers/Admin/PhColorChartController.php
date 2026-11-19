<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PhColorChart;
use Illuminate\Http\Request;

class PhColorChartController extends Controller
{
    public function index()
    {
        $charts = PhColorChart::orderBy('indicator')
            ->orderBy('ph_value')
            ->orderBy('hex_value')
            ->get()
            ->groupBy('indicator');

        return view('admin.ph-color-charts', compact('charts'));
    }


    public function toggle(PhColorChart $phColorChart)
    {
        $phColorChart->update(['active' => !$phColorChart->active]);

        $state = $phColorChart->active ? 'activated' : 'deactivated';

        return redirect()->route('admin.ph-color-charts')
            ->with('success', "Entry {$phColorChart->hex_value} ({$phColorChart->indicator}) {$state}.");
    }

    public function destroy(PhColorChart $phColorChart)
    {
        $label = "{$phColorChart->hex_value} / {$phColorChart->indicator} pH {$phColorChart->ph_value}";
        $phColorChart->delete();

        return redirect()->route('admin.ph-color-charts')
            ->with('success', "Color entry \"{$label}\" deleted permanently.");
    }
}
