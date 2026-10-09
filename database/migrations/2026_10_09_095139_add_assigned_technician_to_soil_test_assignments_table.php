<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Assignment visibility is per named technician, not derived from barangay
 * coverage — the admin picks a specific technician for a specific farm.
 * Barangay coverage remains the general access-control boundary; it just
 * isn't the work-queue mechanism.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('soil_test_assignments', function (Blueprint $table) {
            $table->foreignId('assigned_technician_id')->after('farm_id')->constrained('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('soil_test_assignments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_technician_id');
        });
    }
};
