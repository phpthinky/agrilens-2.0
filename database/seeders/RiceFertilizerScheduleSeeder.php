<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RiceFertilizerScheduleSeeder extends Seeder
{
    /**
     * Seed PhilRice standard fertilizer application schedule for Rice.
     *
     * Reference: DA-PhilRice "Abonong Swak" balanced fertilization program
     * Variety maturity: 111–120 days (most common inbred), transplanted.
     * Target yield: 7–8 t/ha (3-application schedule).
     *
     * 1st Application (0–14 DAT):  2 bags 14-14-14
     * 2nd Application (22–26 DAT): 2 bags Urea 46-0-0
     * 3rd Application (32–36 DAT): 2 bags Urea 46-0-0 + 0.5 bag MOP 0-0-60
     *
     * Source: https://www.philrice.gov.ph/balanced-fertilization-cuts-fertilizer-costs-by-p2-4k-experts/
     * Note: Values are PhilRice reference rates. Update via admin panel when
     *       site-specific BSWM soil test-based rates are available.
     */
    public function run(): void
    {
        $rice = \App\Models\Crop::where('name', 'like', '%Rice%')
                                ->orWhere('name', 'like', '%rice%')
                                ->first();

        if (!$rice) {
            $this->command->warn('Rice crop not found in crops table. Skipping schedule seed.');
            return;
        }

        $schedule = [
            [
                'application_order' => 1,
                'application_label' => 'First Application (0-14 DAT*)',
                'fertilizer_type'   => '14-14-14',
                'bags'              => 2.0,
                'notes'             => 'Basal application. PhilRice ref: 111-120 day variety, 7-8 t/ha target.',
            ],
            [
                'application_order' => 2,
                'application_label' => 'Second Application (22-26 DAT)',
                'fertilizer_type'   => '46-0-0',
                'bags'              => 2.0,
                'notes'             => 'At active tillering. PhilRice ref: Urea 46-0-0.',
            ],
            [
                'application_order' => 3,
                'application_label' => 'Third Application (32-36 DAT)',
                'fertilizer_type'   => '46-0-0',
                'bags'              => 2.0,
                'notes'             => 'At panicle initiation. PhilRice ref.',
            ],
            [
                'application_order' => 3,
                'application_label' => 'Third Application (32-36 DAT)',
                'fertilizer_type'   => '0-0-60',
                'bags'              => 0.5,
                'notes'             => 'At panicle initiation. PhilRice ref: MOP 0-0-60.',
            ],
        ];

        foreach ($schedule as $row) {
            \App\Models\CropFertilizerSchedule::updateOrCreate(
                [
                    'crop_id'           => $rice->id,
                    'application_order' => $row['application_order'],
                    'fertilizer_type'   => $row['fertilizer_type'],
                ],
                [
                    'application_label' => $row['application_label'],
                    'bags'              => $row['bags'],
                    'notes'             => $row['notes'],
                ]
            );
        }

        $this->command->info("Rice fertilizer schedule seeded ({$rice->name}, ID {$rice->id}).");
    }
}
