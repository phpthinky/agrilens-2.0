<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Color charts now live only in the database (no hard-coded fallback), so
 * replace whatever is there with the BSWM soil test kit card colors as the
 * default chart, and track where each entry came from:
 *
 *   source = 'card'   — read from the printed BSWM card (this migration)
 *   source = 'device' — captured by an admin with the colorimeter box
 *                       (Admin › Color Calibration), optionally with the
 *                       capture photo in `snapshot`.
 *
 * Card colors were taken from photos of the four kit cards, white-balanced
 * against each card's own white paper. N, K and P cards print only
 * LOW / MEDIUM / HIGH bands, so each swatch gets a ppm inside the band
 * limits used by FertilizerService::getNutrientStatus().
 */
return new class extends Migration
{
    private const PH = [
        'CPR' => ['5.0' => '#C98449', '5.4' => '#915A49', '5.8' => '#73494C', '6.0' => '#72595C'],
        'BCG' => ['4.0' => '#525F46', '4.4' => '#3C4D4A', '4.8' => '#3C6A69', '5.2' => '#59647C'],
        'BTB' => ['6.0' => '#756C49', '6.4' => '#5A6449', '6.8' => '#47544D', '7.2' => '#446B66', '7.6' => '#545F74'],
    ];

    /** [nutrient, category, ppm, hex] */
    private const NPK = [
        ['N', 'low', 15, '#C64A38'],    ['N', 'low', 30, '#735036'],    ['N', 'low', 40, '#5F473D'],
        ['N', 'medium', 60, '#3C5A3C'], ['N', 'medium', 100, '#37533C'], ['N', 'medium', 150, '#39433A'],
        ['N', 'high', 160, '#3E626C'],  ['N', 'high', 200, '#405165'],  ['N', 'high', 240, '#434C57'],

        ['P', 'low', 5, '#8DD9F0'],     ['P', 'low', 12, '#33A1DC'],    ['P', 'medium', 22, '#3370B7'],
        ['P', 'high', 35, '#364659'],   ['P', 'high', 50, '#3E494B'],

        ['K', 'low', 10, '#5A393A'],    ['K', 'medium', 30, '#80403D'], ['K', 'high', 50, '#9E5441'],
    ];

    public function up(): void
    {
        foreach (['ph_color_charts', 'npk_color_charts'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->string('source', 10)->default('card')->after('hex_value');
                $t->string('snapshot')->nullable()->after('source');
            });
        }

        $now = now();

        DB::table('ph_color_charts')->delete();
        foreach (self::PH as $indicator => $points) {
            foreach ($points as $ph => $hex) {
                DB::table('ph_color_charts')->insert([
                    'indicator' => $indicator, 'ph_value' => (float) $ph, 'hex_value' => $hex,
                    'source' => 'card', 'active' => true, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }

        DB::table('npk_color_charts')->delete();
        foreach (self::NPK as [$nutrient, $category, $ppm, $hex]) {
            DB::table('npk_color_charts')->insert([
                'nutrient' => $nutrient, 'category' => $category, 'ppm_value' => $ppm, 'hex_value' => $hex,
                'source' => 'card', 'active' => true, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    /** The previous chart rows are not restored — back the tables up before migrating. */
    public function down(): void
    {
        foreach (['ph_color_charts', 'npk_color_charts'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn(['source', 'snapshot']);
            });
        }
    }
};
