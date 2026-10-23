<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-crop calibration for the live fertilizer recommendation engine
 * (FertilizerScheduleService) — previously hardcoded PHP constants
 * (TIMING/SPLITS) shared across a fixed whitelist of crops. Each row is one
 * of a crop's 3 application stages: a timing label plus the fraction of the
 * crop's total N/P/K target applied at that stage. Per nutrient, the 3
 * stage fractions should sum to 1.0 (or 0.0 if that nutrient isn't
 * scheduled for the crop at all).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fertilizer_schedule_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crop_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('stage');
            $table->string('label');
            $table->decimal('n_fraction', 5, 4)->default(0);
            $table->decimal('p_fraction', 5, 4)->default(0);
            $table->decimal('k_fraction', 5, 4)->default(0);
            $table->timestamps();

            $table->unique(['crop_id', 'stage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fertilizer_schedule_stages');
    }
};
