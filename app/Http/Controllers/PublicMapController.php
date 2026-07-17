<?php

namespace App\Http\Controllers;

use App\Models\Barangay;
use App\Models\Farm;
use App\Models\SoilSample;
use Illuminate\Http\Request;

class PublicMapController extends Controller
{
    public function index()
    {
        $barangays = Barangay::orderBy('name')->get();

        $stats = [
            'total_farms' => Farm::where('is_active', true)->count(),
            'total_analyses' => SoilSample::count(),
            'total_area' => Farm::where('is_active', true)->sum('area_hectares'),
            'barangays' => Barangay::count(),
        ];

        return view('public.map', compact('barangays', 'stats'));
    }

    public function getFarmsData(Request $request)
    {
        $query = Farm::with([
                'locationBarangay',
                'farmer',
                'soilSamples' => fn ($q) => $q->latest('sample_date')->limit(1),
            ])
            ->where('is_active', true)
            ->whereNotNull('display_latitude');

        if ($request->filled('barangay_id')) {
            $query->where('location_barangay_id', $request->barangay_id);
        }

        $farms = $query->get()->map(function ($farm) {
            $latest = $farm->soilSamples->first();

            return [
                'id' => $farm->id,
                'name' => $farm->farm_name,
                'farmer' => $farm->farmer->full_name,
                'barangay' => $farm->locationBarangay->name,
                'area' => number_format($farm->area_hectares ?? 0, 2),
                'farm_type' => $farm->farm_type,
                'polygon' => $farm->polygon_coordinates,
                'center' => [
                    'lat' => $farm->display_latitude,
                    'lng' => $farm->display_longitude,
                ],
                'fertility' => $latest ? [
                    'score' => $latest->fertility_score,
                    'color_class' => $latest->fertilityColorClass(),
                    'analysis_date' => $latest->sample_date->format('M d, Y'),
                    'ph' => $latest->ph_level !== null ? number_format($latest->ph_level, 1) : null,
                    'nitrogen' => $latest->nitrogen_level !== null ? number_format($latest->nitrogen_level, 1) : null,
                    'phosphorus' => $latest->phosphorus_level !== null ? number_format($latest->phosphorus_level, 1) : null,
                    'potassium' => $latest->potassium_level !== null ? number_format($latest->potassium_level, 1) : null,
                    'recommended_crop' => $latest->recommended_crop,
                ] : null,
            ];
        });

        return response()->json(['success' => true, 'farms' => $farms]);
    }

    public function farmDetails(Farm $farm)
    {
        if (!$farm->is_active || !$farm->polygon_coordinates) {
            abort(404, 'Farm not found or not available for public viewing.');
        }

        $farm->load(['farmer', 'locationBarangay']);

        $soilSamples = $farm->soilSamples()->orderByDesc('sample_date')->get();
        $latest = $soilSamples->first();

        $trendAnalysis = $soilSamples->count() >= 2
            ? $this->calculateTrendAnalysis($soilSamples)
            : null;

        $stats = [
            'total_analyses' => $soilSamples->count(),
            'first_analysis_date' => $soilSamples->last()?->sample_date,
            'latest_analysis_date' => $latest?->sample_date,
            'avg_fertility' => $soilSamples->whereNotNull('fertility_score')->avg('fertility_score'),
        ];

        return view('public.farm-details', compact('farm', 'soilSamples', 'latest', 'trendAnalysis', 'stats'));
    }

    private function calculateTrendAnalysis($soilSamples): array
    {
        $sorted = $soilSamples->sortBy('sample_date');

        return [
            'fertility' => $this->calculateParameterTrend($sorted, 'fertility_score'),
            'ph' => $this->calculateParameterTrend($sorted, 'ph_level'),
            'nitrogen' => $this->calculateParameterTrend($sorted, 'nitrogen_level'),
            'phosphorus' => $this->calculateParameterTrend($sorted, 'phosphorus_level'),
            'potassium' => $this->calculateParameterTrend($sorted, 'potassium_level'),
        ];
    }

    private function calculateParameterTrend($samples, string $parameter): ?array
    {
        $values = $samples->pluck($parameter)->filter()->values()->all();

        if (empty($values)) {
            return null;
        }

        return [
            'min' => min($values),
            'max' => max($values),
            'avg' => round(array_sum($values) / count($values), 2),
            'first' => reset($values),
            'latest' => end($values),
            'change' => end($values) - reset($values),
        ];
    }
}
