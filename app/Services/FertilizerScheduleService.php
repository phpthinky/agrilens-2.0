<?php

namespace App\Services;

use App\Models\Crop;
use Illuminate\Database\Eloquent\Collection;

class FertilizerScheduleService
{
    // ────────────────────────────────────────────────────────────────────────────
    // Fertilizer grades used in every schedule
    // ────────────────────────────────────────────────────────────────────────────
    public const FERT_TYPES = ['UREA 46-0-0', 'DAP 18-46-0', 'MOP 0-0-60'];

    // ────────────────────────────────────────────────────────────────────────────
    // Fertilizer options available for the basal (1st) application.
    // n/p/k are decimal fractions (e.g. 0.14 = 14 %).
    // ────────────────────────────────────────────────────────────────────────────
    public const BASAL_FERTILIZERS = [
        'urea'             => ['name' => 'Urea (46-0-0)',            'n' => 0.46, 'p' => 0.00, 'k' => 0.00],
        'complete'         => ['name' => 'Complete (14-14-14)',       'n' => 0.14, 'p' => 0.14, 'k' => 0.14],
        'ammonium_sulfate' => ['name' => 'Ammonium Sulfate (21-0-0)', 'n' => 0.21, 'p' => 0.00, 'k' => 0.00],
    ];

    // ────────────────────────────────────────────────────────────────────────────
    // Fallback timing/split used for a crop with no admin-calibrated schedule
    // (or missing individual stages) — mirrors the old RICE defaults so an
    // uncalibrated crop still produces a sane schedule.
    // ────────────────────────────────────────────────────────────────────────────
    private const DEFAULT_STAGES = [
        1 => ['label' => '1st Application – Basal (At Planting)',   'n' => 0.50, 'p' => 1.00, 'k' => 0.50],
        2 => ['label' => '2nd Application – Topdress (Tillering)',  'n' => 0.25, 'p' => 0.00, 'k' => 0.00],
        3 => ['label' => '3rd Application – Topdress (Reproductive)', 'n' => 0.25, 'p' => 0.00, 'k' => 0.50],
    ];

    /** True once an admin has calibrated at least one stage for this crop. */
    public function hasCalibration(Crop $crop): bool
    {
        return $crop->fertilizerScheduleStages()->exists();
    }

    /**
     * Kept for backward compatibility with callers that treat this as a
     * null-check gate ("does this crop have a defined schedule?"). Returns
     * the crop's own id (as a string) once calibrated, or null otherwise —
     * there's no more shared string key across aliased crop names, each
     * crop is calibrated independently now.
     */
    public function resolveKey(string $cropName): ?string
    {
        $crop = Crop::whereRaw('LOWER(name) = ?', [strtolower(trim($cropName))])->first();

        return ($crop && $this->hasCalibration($crop)) ? (string) $crop->id : null;
    }

    private function stagesFor(Crop $crop): array
    {
        $rows = $crop->fertilizerScheduleStages;

        if ($rows->isEmpty()) {
            return self::DEFAULT_STAGES;
        }

        $stages = [];
        foreach ($rows as $row) {
            $stages[$row->stage] = [
                'label' => $row->label,
                'n' => (float) $row->n_fraction,
                'p' => (float) $row->p_fraction,
                'k' => (float) $row->k_fraction,
            ];
        }

        // Fill in any stage the admin hasn't calibrated yet with the fallback.
        return $stages + self::DEFAULT_STAGES;
    }

    // ────────────────────────────────────────────────────────────────────────────
    // Derive the actual NPK targets (kg/ha) from the Crop model + soil LMH status
    // ────────────────────────────────────────────────────────────────────────────
    public function getTargets(Crop $crop, string $nStatus, string $pStatus, string $kStatus): array
    {
        $pick = fn(string $col, string $status): float => match (strtolower($status)) {
            'low'    => (float) $crop->{"{$col}_low"},
            'medium' => (float) $crop->{"{$col}_med"},
            'high'   => (float) $crop->{"{$col}_high"},
            default  => (float) $crop->{"{$col}_med"},
        };

        return [
            'n' => $pick('n', $nStatus),
            'p' => $pick('p', $pStatus),
            'k' => $pick('k', $kStatus),
        ];
    }

    // ────────────────────────────────────────────────────────────────────────────
    // Core calculation:
    //   Given NPK targets (kg/ha of nutrient) and the crop's calibrated
    //   stages, return the per-stage fertilizer amounts (kg/ha of product).
    //
    //   Fertilizer logic:
    //     • DAP (18-46-0)  → supplies all of P  →  kgDAP = targetP / 0.46
    //     • The N from DAP is deducted from the total N need
    //     • Remaining N supplied by UREA (46-0-0)  →  kgUrea = remainingN / 0.46
    //     • MOP (0-0-60)   → supplies all of K  →  kgMOP = targetK / 0.60
    //
    //   Per-stage fractions come from the crop's calibrated schedule.
    // ────────────────────────────────────────────────────────────────────────────
    public function calculateSchedule(
        float  $targetN,
        float  $targetP,
        float  $targetK,
        Crop   $crop,
        string $basalFertKey = 'urea'
    ): array {
        $basalFert  = self::BASAL_FERTILIZERS[$basalFertKey] ?? self::BASAL_FERTILIZERS['urea'];
        $basalKey   = array_key_exists($basalFertKey, self::BASAL_FERTILIZERS) ? $basalFertKey : 'urea';

        // ── Total product requirements ──────────────────────────────────────────
        $totalDap   = $targetP > 0 ? $targetP / 0.46 : 0;
        $nFromDap   = $totalDap * 0.18;
        $remainingN = max(0, $targetN - $nFromDap);
        $totalUrea  = $remainingN > 0 ? $remainingN / 0.46 : 0;
        $totalMop   = $targetK  > 0 ? $targetK  / 0.60 : 0;

        $stages = $this->stagesFor($crop);

        $result = [];

        foreach ([1, 2, 3] as $stage) {
            $s = $stages[$stage];

            if ($stage === 1 && $basalKey !== 'urea') {
                // ── Basal stage: use selected fertilizer for N ──────────────────
                // Amount of selected fert to deliver the same N as UREA would have
                $stage1N      = $totalUrea * $s['n'] * 0.46;   // kg/ha of N needed
                $basalKgHa    = $basalFert['n'] > 0 ? round($stage1N / $basalFert['n'], 2) : 0;

                // P and K credit from the selected basal fertilizer
                $pCredit = $basalKgHa * $basalFert['p'];
                $kCredit = $basalKgHa * $basalFert['k'];

                // Remaining P and K that DAP / MOP must still cover at stage 1
                $stage1PNeed = $totalDap * $s['p'] * 0.46;
                $stage1KNeed = $totalMop * $s['k'] * 0.60;

                $dap  = round(max(0.0, $stage1PNeed - $pCredit) / 0.46, 2);
                $mop  = round(max(0.0, $stage1KNeed - $kCredit) / 0.60, 2);
                $urea = 0.0;
                $basal = $basalKgHa;
            } else {
                // ── Default: UREA for N ─────────────────────────────────────────
                $dap   = round($totalDap  * $s['p'], 2);
                $urea  = round($totalUrea * $s['n'], 2);
                $mop   = round($totalMop  * $s['k'], 2);
                $basal = 0.0;
            }

            $result[$stage] = [
                'stage'      => $s['label'],
                'basal_fert' => $basal,
                'urea'       => $urea,
                'dap'        => $dap,
                'mop'        => $mop,
                'fertilizers' => [
                    'UREA 46-0-0' => $urea,
                    'DAP 18-46-0' => $dap,
                    'MOP 0-0-60'  => $mop,
                ],
            ];
        }

        $result['basal_fert_info'] = [
            'key'  => $basalKey,
            'name' => $basalFert['name'],
        ];

        $result['totals'] = [
            'basal_fert' => round($result[1]['basal_fert'] + $result[2]['basal_fert'] + $result[3]['basal_fert'], 2),
            'urea'       => round($result[1]['urea']       + $result[2]['urea']       + $result[3]['urea'],       2),
            'dap'        => round($result[1]['dap']        + $result[2]['dap']        + $result[3]['dap'],        2),
            'mop'        => round($result[1]['mop']        + $result[2]['mop']        + $result[3]['mop'],        2),
        ];

        $result['targets'] = [
            'n' => $targetN,
            'p' => $targetP,
            'k' => $targetK,
        ];

        return $result;
    }

    // ────────────────────────────────────────────────────────────────────────────
    // Convenience: resolve crop → targets → schedule in one call.
    // Returns null if the crop has no calibrated schedule.
    // ────────────────────────────────────────────────────────────────────────────
    public function scheduleForCrop(
        Crop   $crop,
        string $nStatus,
        string $pStatus,
        string $kStatus,
        string $basalFertKey = 'urea'
    ): ?array {
        if (!$this->hasCalibration($crop)) {
            return null;
        }

        $targets = $this->getTargets($crop, $nStatus, $pStatus, $kStatus);

        $schedule = $this->calculateSchedule(
            $targets['n'],
            $targets['p'],
            $targets['k'],
            $crop,
            $basalFertKey
        );

        $schedule['crop_name']    = $crop->name;
        $schedule['schedule_key'] = (string) $crop->id;

        return $schedule;
    }

    // ────────────────────────────────────────────────────────────────────────────
    // Return only the active Crop models that have an admin-calibrated
    // schedule, useful for populating the modal dropdown.
    // ────────────────────────────────────────────────────────────────────────────
    public function supportedCrops(): Collection
    {
        return Crop::active()
            ->whereHas('fertilizerScheduleStages')
            ->orderBy('name')
            ->get();
    }
}
