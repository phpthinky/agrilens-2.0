<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mobile colorimetric: the field app reads the colorimeter photos itself,
 * offline, against colour charts it downloaded, and uploads the finished test
 * in one go. client_uuid is the app's own id for that test, so a retried
 * upload returns the sample it already created instead of making a duplicate.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('soil_samples', function (Blueprint $table) {
            $table->uuid('client_uuid')->nullable()->unique()->after('notes');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE soil_samples MODIFY analysis_type ENUM('pending','manual','colorimetric','probe','mobile_colorimetric') NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("UPDATE soil_samples SET analysis_type = 'colorimetric' WHERE analysis_type = 'mobile_colorimetric'");
            DB::statement("ALTER TABLE soil_samples MODIFY analysis_type ENUM('pending','manual','colorimetric','probe') NOT NULL DEFAULT 'pending'");
        }

        Schema::table('soil_samples', function (Blueprint $table) {
            $table->dropUnique(['client_uuid']);
            $table->dropColumn('client_uuid');
        });
    }
};
