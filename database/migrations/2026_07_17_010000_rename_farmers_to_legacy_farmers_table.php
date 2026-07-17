<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * V2's original flat `farmers` table is being replaced by Version 1's
 * richer Farmer/Farm/Barangay module. Existing rows are preserved under
 * `legacy_farmers` and remapped into the new `farmers` table by the
 * `agrilens:migrate-legacy-farmers` command, which re-adds the FK once
 * `soil_samples.farmer_id` values point at valid new rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('soil_samples', function (Blueprint $table) {
            $table->dropForeign(['farmer_id']);
        });

        Schema::rename('farmers', 'legacy_farmers');
    }

    public function down(): void
    {
        Schema::rename('legacy_farmers', 'farmers');

        Schema::table('soil_samples', function (Blueprint $table) {
            $table->foreign('farmer_id')->references('id')->on('farmers')->nullOnDelete();
        });
    }
};
