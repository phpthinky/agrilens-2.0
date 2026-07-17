<?php

namespace App\Console\Commands;

use App\Models\Farmer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One-time migration of V2's original flat `legacy_farmers` rows into the
 * new Version-1-shaped `farmers` table, remapping every soil_samples.farmer_id
 * that pointed at the old rows. Run once, after the schema migrations that
 * rename farmers -> legacy_farmers and create the new farmers/farms/barangays
 * tables, and before dropping legacy_farmers.
 */
class MigrateLegacyFarmers extends Command
{
    protected $signature = 'agrilens:migrate-legacy-farmers {--dry-run : Report what would happen without writing anything}';

    protected $description = 'Migrate legacy_farmers rows into the new Farmer schema and remap soil_samples.farmer_id';

    public function handle(): int
    {
        if (!Schema::hasTable('legacy_farmers')) {
            $this->error('legacy_farmers table not found — has the rename migration run?');
            return self::FAILURE;
        }

        $dryRun = $this->option('dry-run');
        $legacyFarmers = DB::table('legacy_farmers')->get();

        if ($legacyFarmers->isEmpty()) {
            $this->info('No legacy_farmers rows to migrate.');
        }

        $idMap = [];

        DB::transaction(function () use ($legacyFarmers, $dryRun, &$idMap) {
            foreach ($legacyFarmers as $legacy) {
                [$firstName, $lastName] = $this->splitName($legacy->name);

                if ($dryRun) {
                    $this->line("Would migrate legacy_farmers#{$legacy->id} ({$legacy->name}) -> new farmers row");
                    continue;
                }

                $farmer = Farmer::create([
                    'barangay_id' => null,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'gender' => 'Other',
                    'address' => $legacy->address ?: 'Unknown',
                    'farmer_type' => 'Owner',
                    'is_active' => true,
                    'notes' => $legacy->farm_location
                        ? "Migrated from legacy farmer record. Original farm_location: {$legacy->farm_location}"
                        : 'Migrated from legacy farmer record.',
                ]);

                $idMap[$legacy->id] = $farmer->id;
            }

            if ($dryRun) {
                return;
            }

            foreach ($idMap as $oldId => $newId) {
                DB::table('soil_samples')->where('farmer_id', $oldId)->update(['farmer_id' => $newId]);
            }

            $orphaned = DB::table('soil_samples')
                ->whereNotNull('farmer_id')
                ->whereNotIn('farmer_id', array_values($idMap) ?: [0])
                ->count();

            if ($orphaned > 0) {
                throw new \RuntimeException("{$orphaned} soil_samples row(s) still reference an unmapped farmer_id — aborting before adding the FK constraint.");
            }

            try {
                Schema::table('soil_samples', function ($table) {
                    $table->foreign('farmer_id')->references('id')->on('farmers')->nullOnDelete();
                });
            } catch (\Illuminate\Database\QueryException $e) {
                // Constraint already exists (safe to ignore if this command is re-run).
            }
        });

        if ($dryRun) {
            $this->info("Dry run complete. Would migrate {$legacyFarmers->count()} farmer(s).");
            return self::SUCCESS;
        }

        $this->info('Migrated ' . count($idMap) . ' farmer(s). soil_samples.farmer_id remapped and FK restored.');
        $this->comment('legacy_farmers has been left in place — drop it in a follow-up migration once this has been verified.');

        return self::SUCCESS;
    }

    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name), 2);
        return [$parts[0] ?: 'Unknown', $parts[1] ?? '(unspecified)'];
    }
}
