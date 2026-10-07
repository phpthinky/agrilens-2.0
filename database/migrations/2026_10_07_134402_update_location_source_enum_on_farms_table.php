<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * 'polygon' is renamed to 'live' now that farm boundaries are always GPS-point
     * based (Live GPS Capture or Manual GPS Entry) rather than drawn on the map.
     */
    public function up(): void
    {
        // Widen the enum first so 'live' is a valid value before any row is set to it.
        DB::statement("ALTER TABLE farms MODIFY location_source ENUM('polygon', 'live', 'manual', 'corrected') NOT NULL DEFAULT 'polygon'");
        DB::statement("UPDATE farms SET location_source = 'live' WHERE location_source = 'polygon'");
        DB::statement("ALTER TABLE farms MODIFY location_source ENUM('live', 'manual', 'corrected') NOT NULL DEFAULT 'live'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE farms MODIFY location_source ENUM('polygon', 'live', 'manual', 'corrected') NOT NULL DEFAULT 'polygon'");
        DB::statement("UPDATE farms SET location_source = 'polygon' WHERE location_source = 'live'");
        DB::statement("ALTER TABLE farms MODIFY location_source ENUM('polygon', 'manual', 'corrected') NOT NULL DEFAULT 'polygon'");
    }
};
