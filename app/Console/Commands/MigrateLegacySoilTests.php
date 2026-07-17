<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One-time migration of Version 1's `soil_tests` rows into the unified
 * `soil_samples` table as analysis_type=manual entries.
 *
 * Prerequisite: V1's `barangays`, `farmers`, `farms`, `soil_tests`, and
 * `users` data must already be present in this database (e.g. imported via
 * a one-time SQL dump from V1's production database) with farm_id/farmer_id/
 * created_by values that resolve against the farms/farmers/users tables
 * already in this database — this command does not perform that import
 * itself, only the soil_tests -> soil_samples mapping on top of it.
 */
class MigrateLegacySoilTests extends Command
{
    protected $signature = 'agrilens:migrate-legacy-soil-tests {--dry-run : Report what would happen without writing anything}';

    protected $description = 'Migrate V1 soil_tests rows into the unified soil_samples table as manual analyses';

    public function handle(): int
    {
        if (!Schema::hasTable('soil_tests')) {
            $this->error('soil_tests table not found in this database — nothing to migrate.');
            return self::FAILURE;
        }

        $dryRun = $this->option('dry-run');
        $rows = DB::table('soil_tests')->get();

        if ($rows->isEmpty()) {
            $this->info('No soil_tests rows to migrate.');
            return self::SUCCESS;
        }

        $skipped = 0;
        $migrated = 0;
        $orphaned = [];

        DB::transaction(function () use ($rows, $dryRun, &$skipped, &$migrated, &$orphaned) {
            foreach ($rows as $row) {
                $farmExists = DB::table('farms')->where('id', $row->farm_id)->exists();
                $userExists = DB::table('users')->where('id', $row->created_by)->exists();

                if (!$farmExists || !$userExists) {
                    $orphaned[] = $row->id;
                    continue;
                }

                // Skip if a manual sample for this exact farm+date already exists
                // (cheap re-run guard — not a strict dedup key, but good enough
                // for a one-time cutover run against a not-yet-live table).
                $alreadyMigrated = DB::table('soil_samples')
                    ->where('farm_id', $row->farm_id)
                    ->where('analysis_type', 'manual')
                    ->where('sample_date', $row->test_date)
                    ->where('ph_level', $row->ph_level)
                    ->exists();

                if ($alreadyMigrated) {
                    $skipped++;
                    continue;
                }

                if ($dryRun) {
                    $this->line("Would migrate soil_tests#{$row->id} (farm_id={$row->farm_id}, test_date={$row->test_date})");
                    $migrated++;
                    continue;
                }

                $farm = DB::table('farms')->where('id', $row->farm_id)->first();

                DB::table('soil_samples')->insert([
                    'user_id' => $row->created_by,
                    'farmer_id' => $farm->farmer_id,
                    'farm_id' => $row->farm_id,
                    'analysis_type' => 'manual',
                    'soil_type' => $row->soil_type,
                    'sample_name' => 'Migrated soil test #' . $row->id,
                    'sample_date' => $row->test_date,
                    'date_tested' => $row->test_date,
                    'farmer_name' => '',
                    'address' => '',
                    'color_hex' => '#8B4513',
                    'ph_level' => $row->ph_level,
                    'nitrogen_level' => $row->nitrogen_level,
                    'phosphorus_level' => $row->phosphorus_level,
                    'potassium_level' => $row->potassium_level,
                    'fertility_score' => $row->system_fertility_score ?? $row->arduino_fertility_score ?? $row->fertility_score,
                    'analyzed_at' => $row->calculated_at ?? $row->created_at,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);

                $migrated++;
            }
        });

        if (!empty($orphaned)) {
            $this->warn('Skipped ' . count($orphaned) . ' row(s) with no matching farm/user: ' . implode(', ', $orphaned));
        }
        if ($skipped > 0) {
            $this->comment("Skipped {$skipped} row(s) already migrated.");
        }

        $label = $dryRun ? 'Would migrate' : 'Migrated';
        $this->info("{$label} {$migrated} soil_tests row(s) into soil_samples.");

        return self::SUCCESS;
    }
}
