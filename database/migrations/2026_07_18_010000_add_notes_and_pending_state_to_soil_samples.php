<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Splits sample creation from analysis-method selection: a sample now exists
 * in a 'pending' state (no soil parameters yet) between "Create Sample" and
 * "Select Analysis Type", per the sample-first workflow.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('soil_samples', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('probe_raw_payload');
        });

        DB::statement("ALTER TABLE soil_samples MODIFY analysis_type ENUM('pending','manual','colorimetric','probe') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("UPDATE soil_samples SET analysis_type = 'colorimetric' WHERE analysis_type = 'pending'");
        DB::statement("ALTER TABLE soil_samples MODIFY analysis_type ENUM('manual','colorimetric','probe') NOT NULL DEFAULT 'colorimetric'");

        Schema::table('soil_samples', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
    }
};
