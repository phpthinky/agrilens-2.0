<?php

namespace Database\Seeders;

use App\Models\Crop;
use App\Models\FertilizerScheduleStage;
use Illuminate\Database\Seeder;

/**
 * Seeds fertilizer_schedule_stages with the values that used to be hardcoded
 * PHP constants in FertilizerScheduleService (TIMING/SPLITS), so existing
 * crops keep producing the exact same recommendations after the switch to
 * admin-calibrated, DB-backed schedules. Idempotent — safe to re-run.
 */
class FertilizerScheduleStageSeeder extends Seeder
{
    // Timing label + N/P/K split fraction per stage, keyed by the old
    // hardcoded schedule key (one set of values, applied to every crop name
    // that used to alias to that key).
    private const DEFAULTS = [
        'RICE' => [
            1 => ['label' => '1st Application – 0–2 DAT (Basal, Before/At Planting)',       'n' => 0.50,  'p' => 1.00, 'k' => 0.50],
            2 => ['label' => '2nd Application – 21–30 DAT (Topdress, Tillering)',           'n' => 0.25,  'p' => 0.00, 'k' => 0.00],
            3 => ['label' => '3rd Application – 50–60 DAT (Topdress, Panicle Initiation)',  'n' => 0.25,  'p' => 0.00, 'k' => 0.50],
        ],
        'CORN' => [
            1 => ['label' => '1st Application – 0–2 DAP (Basal, At Planting)',              'n' => 0.333, 'p' => 1.00, 'k' => 0.50],
            2 => ['label' => '2nd Application – 25–30 DAP (Topdress, Knee-high Stage)',     'n' => 0.333, 'p' => 0.00, 'k' => 0.00],
            3 => ['label' => '3rd Application – 45–55 DAP (Topdress, Tasseling Stage)',     'n' => 0.334, 'p' => 0.00, 'k' => 0.50],
        ],
        'TOMATO' => [
            1 => ['label' => '1st Application – 0 DAT (Basal, At Transplanting)',           'n' => 0.25,  'p' => 1.00, 'k' => 0.25],
            2 => ['label' => '2nd Application – 21–30 DAT (Topdress, Before Flowering)',    'n' => 0.50,  'p' => 0.00, 'k' => 0.25],
            3 => ['label' => '3rd Application – 45–60 DAT (Topdress, Fruit Setting)',       'n' => 0.25,  'p' => 0.00, 'k' => 0.50],
        ],
        'EGGPLANT' => [
            1 => ['label' => '1st Application – 0 DAT (Basal, At Transplanting)',           'n' => 0.50,  'p' => 1.00, 'k' => 0.50],
            2 => ['label' => '2nd Application – 30 DAT (Topdress, Vegetative Growth)',      'n' => 0.25,  'p' => 0.00, 'k' => 0.00],
            3 => ['label' => '3rd Application – 55–60 DAT (Topdress, First Harvest/Peak)',  'n' => 0.25,  'p' => 0.00, 'k' => 0.50],
        ],
        'CHILI' => [
            1 => ['label' => '1st Application – 0 DAT (Basal, At Transplanting)',           'n' => 0.25,  'p' => 1.00, 'k' => 0.50],
            2 => ['label' => '2nd Application – 30 DAT (Topdress, Flower Initiation)',      'n' => 0.50,  'p' => 0.00, 'k' => 0.00],
            3 => ['label' => '3rd Application – 60 DAT (Topdress, Fruiting Stage)',         'n' => 0.25,  'p' => 0.00, 'k' => 0.50],
        ],
        'BITTER GOURD' => [
            1 => ['label' => '1st Application – 0 DAP (Basal, At Planting)',                'n' => 0.333, 'p' => 1.00, 'k' => 0.333],
            2 => ['label' => '2nd Application – 28–30 DAP (Topdress, Vining Stage)',        'n' => 0.333, 'p' => 0.00, 'k' => 0.333],
            3 => ['label' => '3rd Application – 50–60 DAP (Topdress, Peak Flowering)',      'n' => 0.334, 'p' => 0.00, 'k' => 0.334],
        ],
        'BEANS' => [
            1 => ['label' => '1st Application – 0 DAP (Basal, At Planting)',                'n' => 0.50,  'p' => 1.00, 'k' => 1.00],
            2 => ['label' => '2nd Application – 20–25 DAP (Topdress, Pre-flowering)',       'n' => 0.50,  'p' => 0.00, 'k' => 0.00],
            3 => ['label' => '3rd Application – (Rarely needed)',                           'n' => 0.00,  'p' => 0.00, 'k' => 0.00],
        ],
    ];

    // Crop names (as they currently exist in the crops table, matched
    // case-insensitively) that previously aliased to each schedule key above.
    private const CROP_NAME_KEYS = [
        'RICE'                    => 'RICE',
        'CORN(WHITE)'             => 'CORN',
        'CORN(YELLOW)'            => 'CORN',
        'CORN (WHITE)'            => 'CORN',
        'CORN (YELLOW)'           => 'CORN',
        'TOMATOES'                => 'TOMATO',
        'TOMATO'                  => 'TOMATO',
        'AMPALAYA/UPO'            => 'BITTER GOURD',
        'AMPALAYA'                => 'BITTER GOURD',
        'BITTER GOURD'            => 'BITTER GOURD',
        'EGGPLANT'                => 'EGGPLANT',
        'CHILI'                   => 'CHILI',
        'BELL PEPPER (CHILI)'     => 'CHILI',
        'GREEN (SINIGANG CHILI)'  => 'CHILI',
        'BEANS'                   => 'BEANS',
        'STRING BEANS'            => 'BEANS',
        'BATAO'                   => 'BEANS',
        'PEAS'                    => 'BEANS',
    ];

    public function run(): void
    {
        $crops = Crop::all()->keyBy(fn(Crop $c) => strtoupper(trim($c->name)));

        foreach (self::CROP_NAME_KEYS as $cropNameUpper => $scheduleKey) {
            $crop = $crops->get($cropNameUpper);
            if (!$crop) {
                continue;
            }

            foreach (self::DEFAULTS[$scheduleKey] as $stage => $data) {
                FertilizerScheduleStage::updateOrCreate(
                    ['crop_id' => $crop->id, 'stage' => $stage],
                    [
                        'label' => $data['label'],
                        'n_fraction' => $data['n'],
                        'p_fraction' => $data['p'],
                        'k_fraction' => $data['k'],
                    ]
                );
            }
        }
    }
}
