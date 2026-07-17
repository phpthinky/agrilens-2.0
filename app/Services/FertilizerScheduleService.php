<?php

namespace App\Services;

use App\Models\Crop;

class FertilizerScheduleService
{
    // ────────────────────────────────────────────────────────────────────────────
    // Crops that have a defined fertilizer application timing schedule.
    // Keys MUST match crop names in the DB (case-insensitive compare is done
    // in getCropSchedule()).  Only these crops appear in the modal selector.
    // ────────────────────────────────────────────────────────────────────────────
    public const SUPPORTED_CROPS = [
        'RICE',
        'CORN',
        'TOMATO',
        'EGGPLANT',
        'CHILI',
        'BITTER GOURD',
        'BEANS',
    ];

    // ────────────────────────────────────────────────────────────────────────────
    // Per-crop application timing labels  (3 stages each)
    // ────────────────────────────────────────────────────────────────────────────
    protected const TIMING = [
        'RICE' => [
            1 => '1st Application – 0–2 DAT (Basal, Before/At Planting)',
            2 => '2nd Application – 21–30 DAT (Topdress, Tillering)',
            3 => '3rd Application – 50–60 DAT (Topdress, Panicle Initiation)',
        ],
        'CORN' => [
            1 => '1st Application – 0–2 DAP (Basal, At Planting)',
            2 => '2nd Application – 25–30 DAP (Topdress, Knee-high Stage)',
            3 => '3rd Application – 45–55 DAP (Topdress, Tasseling Stage)',
        ],
        'TOMATO' => [
            1 => '1st Application – 0 DAT (Basal, At Transplanting)',
            2 => '2nd Application – 21–30 DAT (Topdress, Before Flowering)',
            3 => '3rd Application – 45–60 DAT (Topdress, Fruit Setting)',
        ],
        'EGGPLANT' => [
            1 => '1st Application – 0 DAT (Basal, At Transplanting)',
            2 => '2nd Application – 30 DAT (Topdress, Vegetative Growth)',
            3 => '3rd Application – 55–60 DAT (Topdress, First Harvest/Peak)',
        ],
        'CHILI' => [
            1 => '1st Application – 0 DAT (Basal, At Transplanting)',
            2 => '2nd Application – 30 DAT (Topdress, Flower Initiation)',
            3 => '3rd Application – 60 DAT (Topdress, Fruiting Stage)',
        ],
        'BITTER GOURD' => [
            1 => '1st Application – 0 DAP (Basal, At Planting)',
            2 => '2nd Application – 28–30 DAP (Topdress, Vining Stage)',
            3 => '3rd Application – 50–60 DAP (Topdress, Peak Flowering)',
        ],
        'BEANS' => [
            1 => '1st Application – 0 DAP (Basal, At Planting)',
            2 => '2nd Application – 20–25 DAP (Topdress, Pre-flowering)',
            3 => '3rd Application – (Rarely needed)',
        ],
    ];

    // ────────────────────────────────────────────────────────────────────────────
    // N/P/K split ratios per crop per stage.
    // Each entry is [n_fraction, p_fraction, k_fraction].
    //   • P is always applied fully in the 1st application (basal).
    //   • Fractions must sum to 1.0 for each nutrient across all 3 stages.
    // ────────────────────────────────────────────────────────────────────────────
    protected const SPLITS = [
        // Rice:  N 1/2 | 1/4 | 1/4 ;  P full|0|0 ;  K 1/2|0|1/2
        'RICE' => [
            1 => ['n' => 0.50, 'p' => 1.00, 'k' => 0.50],
            2 => ['n' => 0.25, 'p' => 0.00, 'k' => 0.00],
            3 => ['n' => 0.25, 'p' => 0.00, 'k' => 0.50],
        ],
        // Corn:  N 1/3 | 1/3 | 1/3 ;  P full|0|0 ;  K 1/2|0|1/2
        'CORN' => [
            1 => ['n' => 0.333, 'p' => 1.00, 'k' => 0.50],
            2 => ['n' => 0.333, 'p' => 0.00, 'k' => 0.00],
            3 => ['n' => 0.334, 'p' => 0.00, 'k' => 0.50],
        ],
        // Tomato: N 1/4 | 1/2 | 1/4 ;  P full|0|0 ;  K 1/4|1/4|1/2
        'TOMATO' => [
            1 => ['n' => 0.25, 'p' => 1.00, 'k' => 0.25],
            2 => ['n' => 0.50, 'p' => 0.00, 'k' => 0.25],
            3 => ['n' => 0.25, 'p' => 0.00, 'k' => 0.50],
        ],
        // Eggplant: N 1/2 | 1/4 | 1/4 ;  P full|0|0 ;  K 1/2|0|1/2
        'EGGPLANT' => [
            1 => ['n' => 0.50, 'p' => 1.00, 'k' => 0.50],
            2 => ['n' => 0.25, 'p' => 0.00, 'k' => 0.00],
            3 => ['n' => 0.25, 'p' => 0.00, 'k' => 0.50],
        ],
        // Chili: N 1/4 | 1/2 | 1/4 ;  P full|0|0 ;  K 1/2|0|1/2
        'CHILI' => [
            1 => ['n' => 0.25, 'p' => 1.00, 'k' => 0.50],
            2 => ['n' => 0.50, 'p' => 0.00, 'k' => 0.00],
            3 => ['n' => 0.25, 'p' => 0.00, 'k' => 0.50],
        ],
        // Bitter Gourd: N 1/3 | 1/3 | 1/3 ;  P full|0|0 ;  K 1/3|1/3|1/3
        'BITTER GOURD' => [
            1 => ['n' => 0.333, 'p' => 1.00, 'k' => 0.333],
            2 => ['n' => 0.333, 'p' => 0.00, 'k' => 0.333],
            3 => ['n' => 0.334, 'p' => 0.00, 'k' => 0.334],
        ],
        // Beans: N 1/2 | 1/2 | 0 ;  P full|0|0 ;  K full|0|0  (3rd rarely needed)
        'BEANS' => [
            1 => ['n' => 0.50, 'p' => 1.00, 'k' => 1.00],
            2 => ['n' => 0.50, 'p' => 0.00, 'k' => 0.00],
            3 => ['n' => 0.00, 'p' => 0.00, 'k' => 0.00],
        ],
    ];

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
    // Map a crop name (from DB) to its uppercase schedule key, or null if the
    // crop is not in the supported list.
    // ────────────────────────────────────────────────────────────────────────────
    public function resolveKey(string $cropName): ?string
    {
        $upper = strtoupper(trim($cropName));

        // Alias map for crop names that differ between DB and schedule keys
        $aliases = [
            'TOMATOES'      => 'TOMATO',
            'CORN(WHITE)'   => 'CORN',
            'CORN(YELLOW)'  => 'CORN',
            'CORN (WHITE)'  => 'CORN',
            'CORN (YELLOW)' => 'CORN',
            'RICE'          => 'RICE',
            'AMPALAYA/UPO'  => 'BITTER GOURD',   // ampalaya = bitter gourd
            'AMPALAYA'      => 'BITTER GOURD',
            'BITTER GOURD'  => 'BITTER GOURD',
            'EGGPLANT'      => 'EGGPLANT',
            'CHILI'         => 'CHILI',
            'BELL PEPPER (CHILI)' => 'CHILI',
            'GREEN (SINIGANG CHILI)' => 'CHILI',
            'BEANS'         => 'BEANS',
            'STRING BEANS'  => 'BEANS',
            'BATAO'         => 'BEANS',
            'PEAS'          => 'BEANS',
            'TOMATO'        => 'TOMATO',
        ];

        return $aliases[$upper] ?? (in_array($upper, self::SUPPORTED_CROPS, true) ? $upper : null);
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
    //   Given NPK targets (kg/ha of nutrient) and a schedule key, return the
    //   per-stage fertilizer amounts (kg/ha of product).
    //
    //   Fertilizer logic:
    //     • DAP (18-46-0)  → supplies all of P  →  kgDAP = targetP / 0.46
    //     • The N from DAP is deducted from the total N need
    //     • Remaining N supplied by UREA (46-0-0)  →  kgUrea = remainingN / 0.46
    //     • MOP (0-0-60)   → supplies all of K  →  kgMOP = targetK / 0.60
    //
    //   Splits are applied per stage using the fraction tables above.
    // ────────────────────────────────────────────────────────────────────────────
    public function calculateSchedule(
        float  $targetN,
        float  $targetP,
        float  $targetK,
        string $scheduleKey,
        string $basalFertKey = 'urea'
    ): array {
        $key        = strtoupper($scheduleKey);
        $basalFert  = self::BASAL_FERTILIZERS[$basalFertKey] ?? self::BASAL_FERTILIZERS['urea'];
        $basalKey   = array_key_exists($basalFertKey, self::BASAL_FERTILIZERS) ? $basalFertKey : 'urea';

        // ── Total product requirements ──────────────────────────────────────────
        $totalDap   = $targetP > 0 ? $targetP / 0.46 : 0;
        $nFromDap   = $totalDap * 0.18;
        $remainingN = max(0, $targetN - $nFromDap);
        $totalUrea  = $remainingN > 0 ? $remainingN / 0.46 : 0;
        $totalMop   = $targetK  > 0 ? $targetK  / 0.60 : 0;

        $splits  = self::SPLITS[$key]  ?? self::SPLITS['RICE'];
        $timings = self::TIMING[$key]  ?? self::TIMING['RICE'];

        $result = [];

        foreach ([1, 2, 3] as $stage) {
            $s = $splits[$stage];

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
                'stage'      => $timings[$stage],
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
    // Returns null if the crop has no schedule defined.
    // ────────────────────────────────────────────────────────────────────────────
    public function scheduleForCrop(
        Crop   $crop,
        string $nStatus,
        string $pStatus,
        string $kStatus,
        string $basalFertKey = 'urea'
    ): ?array {
        $key = $this->resolveKey($crop->name);
        if ($key === null) {
            return null;
        }

        $targets = $this->getTargets($crop, $nStatus, $pStatus, $kStatus);

        $schedule = $this->calculateSchedule(
            $targets['n'],
            $targets['p'],
            $targets['k'],
            $key,
            $basalFertKey
        );

        $schedule['crop_name']    = $crop->name;
        $schedule['schedule_key'] = $key;

        return $schedule;
    }

    // ────────────────────────────────────────────────────────────────────────────
    // Return only the active Crop models that have a supported schedule,
    // useful for populating the modal dropdown.
    // ────────────────────────────────────────────────────────────────────────────
    public function supportedCrops(): \Illuminate\Database\Eloquent\Collection
    {
        return Crop::active()
            ->orderBy('name')
            ->get()
            ->filter(fn(Crop $c) => $this->resolveKey($c->name) !== null)
            ->values();
    }
}