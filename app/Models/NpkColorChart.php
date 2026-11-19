<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NpkColorChart extends Model
{
    public const NAMES = ['N' => 'Nitrogen', 'P' => 'Phosphorus', 'K' => 'Potassium'];

    /**
     * ppm stored for a calibration capture labelled only LOW / MEDIUM / HIGH:
     * a value inside each band of FertilizerService::getNutrientStatus()
     * (N <45 / 45–160 / ≥160, P <15 / 15–30 / ≥30, K <20 / 20–40 / ≥40).
     */
    public const DEFAULT_PPM = [
        'N' => ['low' => 30.0, 'medium' => 100.0, 'high' => 200.0],
        'P' => ['low' => 8.0,  'medium' => 22.0,  'high' => 40.0],
        'K' => ['low' => 10.0, 'medium' => 30.0,  'high' => 50.0],
    ];

    protected $fillable = ['nutrient', 'ppm_value', 'hex_value', 'category', 'source', 'snapshot', 'active'];

    protected $casts = [
        'ppm_value' => 'float',
        'active'    => 'boolean',
    ];

    /**
     * Returns active color chart as [hex_value => ppm_value] for a given nutrient.
     * Used by ColorScienceService for CIEDE2000 delta-E matching.
     */
    public static function chartForNutrient(string $nutrient): array
    {
        return static::where('nutrient', strtoupper($nutrient))
            ->where('active', true)
            ->orderBy('ppm_value')
            ->get()
            ->mapWithKeys(fn($row) => [$row->hex_value => $row->ppm_value])
            ->toArray();
    }
}
