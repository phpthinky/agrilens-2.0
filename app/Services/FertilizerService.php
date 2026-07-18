<?php

namespace App\Services;

/**
 * BSWM/PhilRice fertilizer recommendation engine.
 * Ported from old-app/config.php getFertilizerRecommendation().
 */
class FertilizerService
{
    /** $ph is nullable because Digital Probe readings don't measure pH. */
    public function recommend(?float $ph, float $n, float $p, float $k): array
    {
        $rec = ['urea_kgha' => 0.0, 'tsp_kgha' => 0.0, 'mop_kgha' => 0.0, 'notes' => []];

        // pH assessment
        if ($ph === null) {
            $rec['notes'][] = 'pH was not measured (Digital Probe reading) — recommendations below are based on N/P/K only.';
        } elseif ($ph < 5.5) {
            $rec['notes'][] = 'Soil pH is acidic — this must be addressed, as some crops may not thrive or may not perform at their best under acidic soil conditions.';
        } elseif ($ph > 7.5) {
            $rec['notes'][] = 'Soil is alkaline (pH > 7.5). Consider incorporating organic matter or elemental sulfur to lower pH.';
        }

        // Nitrogen (Urea 46-0-0)
        if ($n < 45) {
            $rec['urea_kgha'] = 200.0;
            $rec['notes'][]   = 'Low nitrogen. Apply Urea in 2 splits: ½ basal + ½ at panicle initiation.';
        } elseif ($n < 160) {
            $rec['urea_kgha'] = 125.0;
            $rec['notes'][]   = 'Medium nitrogen. Apply Urea in 2 splits: ½ basal + ½ at active tillering.';
        } else {
            $rec['urea_kgha'] = 50.0;
            $rec['notes'][]   = 'Adequate nitrogen. Apply minimal Urea (50 kg/ha) as maintenance only.';
        }

        // Phosphorus (TSP 0-46-0)
        if ($p < 15) {
            $rec['tsp_kgha'] = 125.0;
            $rec['notes'][]  = 'Low phosphorus. Apply TSP basally (at planting) for root development.';
        } elseif ($p < 30) {
            $rec['tsp_kgha'] = 75.0;
            $rec['notes'][]  = 'Medium phosphorus. Apply TSP basally to maintain P availability.';
        } else {
            $rec['tsp_kgha'] = 0.0;
            $rec['notes'][]  = 'Adequate phosphorus. No TSP needed this season.';
        }

        // Potassium (MOP 0-0-60)
        if ($k < 20) {
            $rec['mop_kgha'] = 100.0;
            $rec['notes'][]  = 'Low potassium. Apply MOP basally. K improves drought tolerance and grain quality.';
        } elseif ($k < 40) {
            $rec['mop_kgha'] = 50.0;
            $rec['notes'][]  = 'Medium potassium. Apply 50 kg MOP/ha as basal application.';
        } else {
            $rec['mop_kgha'] = 0.0;
            $rec['notes'][]  = 'Adequate potassium. No MOP needed this season.';
        }

        $rec['notes'][] = 'Recommendation basis: BSWM/PhilRice colorimetric soil test guidelines (per hectare). Verify with a certified soil laboratory for large-scale production decisions.';

        return $rec;
    }

    public function summary(array $rec): string
    {
        return sprintf(
            'Urea (46-0-0): %.1f kg/ha | TSP (0-46-0): %.1f kg/ha | MOP (0-0-60): %.1f kg/ha',
            $rec['urea_kgha'], $rec['tsp_kgha'], $rec['mop_kgha']
        );
    }

    public function getNutrientStatus(string $parameter, float $value): string
    {
        $thresholds = [
            'ph'         => ['low_max' => 5.5,  'high_min' => 7.0],
            'nitrogen'   => ['low_max' => 45.0, 'high_min' => 160.0],
            'phosphorus' => ['low_max' => 15.0, 'high_min' => 30.0],
            'potassium'  => ['low_max' => 20.0, 'high_min' => 40.0],
        ];
        if (!isset($thresholds[$parameter])) return 'Medium';
        $t = $thresholds[$parameter];
        if ($parameter === 'ph') {
            if ($value < $t['low_max'])  return 'Acidic';
            if ($value > $t['high_min']) return 'Alkaline';
            return 'Optimal';
        }
        if ($value < $t['low_max'])   return 'Low';
        if ($value >= $t['high_min']) return 'High';
        return 'Medium';
    }

    /**
     * $ph is nullable because Digital Probe readings don't measure pH — when
     * absent, the score is computed from N/P/K alone, with their weights
     * (0.35 + 0.25 + 0.25 = 0.85) renormalized to sum to 1.0.
     */
    public function computeFertilityScore(?float $ph, float $n, float $p, float $k): int
    {
        $nScore = match (true) {
            $n >= 60  && $n <= 150  => 100,
            $n >= 45  && $n < 60    => 80,
            $n >= 160               => 80,
            $n >= 15                => 50,
            default                 => 15,
        };
        $pScore = match (true) {
            $p >= 15 && $p <= 30  => 100,
            $p > 30 && $p <= 50   => 75,
            $p >= 8               => 50,
            default               => 15,
        };
        $kScore = match (true) {
            $k >= 20 && $k <= 40  => 100,
            $k > 40 && $k <= 70   => 75,
            $k >= 10              => 50,
            default               => 15,
        };

        if ($ph === null) {
            return (int) round(($nScore * 0.35 + $pScore * 0.25 + $kScore * 0.25) / 0.85);
        }

        $phScore = match (true) {
            $ph >= 6.0 && $ph <= 7.0 => 100,
            $ph >= 5.5 && $ph <= 7.5 => 70,
            $ph >= 5.0 && $ph <= 8.0 => 40,
            default => 10,
        };

        return (int) round($nScore * 0.35 + $pScore * 0.25 + $kScore * 0.25 + $phScore * 0.15);
    }
}
