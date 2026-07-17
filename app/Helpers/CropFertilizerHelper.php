<?php

namespace App\Helpers;

class CropFertilizerHelper
{
    /**
     * 1️⃣ Reversed LMH classification
     * High soil → Low fertilizer need
     * Low soil → High fertilizer need
     */
  /*
    public static function classifyReversed(float $soilValue, float $cropLow, float $cropMed, float $cropHigh): string
    {
        if ($soilValue >= $cropHigh) return 'Low';
        if ($soilValue >= $cropMed) return 'Medium';
        return 'High';
    }
*/
    public static function classifyReversed(float $soilValue, float $cropLow, float $cropMed, float $cropHigh): string
{
    if ($soilValue >= $cropLow)  return 'Low';
    if ($soilValue >= $cropMed)  return 'Medium';
    return 'High';
}

    /**
     * 2️⃣ Compute deficit (target – soil)
     */
    public static function computeDeficit(float $soilValue, float $targetValue): float
    {
        return max(0, $targetValue - $soilValue);
    }

    /**
     * 3️⃣ Return crop target based on reversed status
     */
/*
    public static function getTargetByStatus(string $status, float $low, float $med, float $high): float
    {
        return match($status) {
            'Low' => $low,
            'Medium' => $med,
            'High' => $high,
        };
    }
*/
    public static function getTargetByStatus(string $status, float $low, float $med, float $high): float
{
    return match($status) {
        'Low'    => $low,
        'Medium' => $med,
        'High'   => $high,
    };
}
    /**
     * 4️⃣ Main computeFertilizer function
     * Returns status, target, deficit, and fertilizer kg/ha
     *
     * @param float $soilN
     * @param float $soilP
     * @param float $soilK
     * @param \App\Models\Crop $crop
     * @param float $soilMassKg default 2_000_000 kg/ha
     * @return array
     */
    /**
     * @param string $nStatus  General soil status for N: 'Low' | 'Medium' | 'High'
     * @param string $pStatus  General soil status for P: 'Low' | 'Medium' | 'High'
     * @param string $kStatus  General soil status for K: 'Low' | 'Medium' | 'High'
     */
    public static function computeFertilizer(
        float $soilN, float $soilP, float $soilK,
        $crop,
        string $nStatus, string $pStatus, string $kStatus
    ): array {
        $result = [];

        $nutrients = [
            'n' => ['soil' => $soilN, 'low' => $crop->n_low, 'med' => $crop->n_med, 'high' => $crop->n_high, 'fraction' => 0.46, 'status' => $nStatus],
            'p' => ['soil' => $soilP, 'low' => $crop->p_low, 'med' => $crop->p_med, 'high' => $crop->p_high, 'fraction' => 0.18, 'status' => $pStatus],
            'k' => ['soil' => $soilK, 'low' => $crop->k_low, 'med' => $crop->k_med, 'high' => $crop->k_high, 'fraction' => 0.60, 'status' => $kStatus],
        ];

        foreach ($nutrients as $key => $data) {
            // Use the same general soil status as "Current Soil Status" table,
            // so Low → n_low column, Medium → n_med, High → n_high.
            $target = self::getTargetByStatus($data['status'], $data['low'], $data['med'], $data['high']);

            // Fertilizer (kg/ha) = Crop Target ppm ÷ Fertilizer Content Fraction
            $fertKgHa = $data['fraction'] > 0 ? $target / $data['fraction'] : 0;

            $result[$key] = [
                'status'     => $data['status'],
                'soil_ppm'   => $data['soil'],
                'target_ppm' => round($target, 2),
                'fert_kg_ha' => round($fertKgHa, 2),
            ];
        }

        return $result;
    }
}