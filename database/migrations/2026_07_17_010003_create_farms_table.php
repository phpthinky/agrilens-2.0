<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farmer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_barangay_id')->constrained('barangays')->cascadeOnDelete();
            $table->string('farm_name');
            $table->text('farm_address')->nullable();
            $table->text('description')->nullable();

            $table->json('polygon_coordinates')->nullable();
            $table->decimal('manual_latitude', 10, 8)->nullable()
                ->comment('Manually entered latitude (if no polygon drawn)');
            $table->decimal('manual_longitude', 11, 8)->nullable()
                ->comment('Manually entered longitude (if no polygon drawn)');
            $table->decimal('display_latitude', 10, 8)->nullable()
                ->comment('Final latitude to display on map - calculated or corrected');
            $table->decimal('display_longitude', 11, 8)->nullable()
                ->comment('Final longitude to display on map - calculated or corrected');
            $table->enum('location_source', ['polygon', 'manual', 'corrected'])->default('polygon');
            $table->text('location_notes')->nullable();
            $table->decimal('area_hectares', 8, 4)->nullable();

            $table->enum('farm_type', [
                'Riceland',
                'Cornland',
                'Vegetable Farm',
                'Fruit Orchard',
                'Coconut Farm',
                'Mixed Crops',
                'Pasture Land',
                'Fish Pond',
                'Other',
            ])->default('Mixed Crops');
            $table->enum('land_tenure', [
                'Owned',
                'Rented',
                'Shared/Partnership',
                'Caretaker',
                'Government Land',
                'Other',
            ])->default('Owned');
            $table->enum('irrigation_type', [
                'Irrigated',
                'Rainfed',
                'Partially Irrigated',
                'Not Applicable',
            ])->default('Rainfed');
            $table->enum('slope_category', [
                'Flat (0-3%)',
                'Gently Rolling (3-8%)',
                'Rolling (8-15%)',
                'Moderately Steep (15-25%)',
                'Steep (25-35%)',
                'Very Steep (>35%)',
            ])->nullable();
            $table->decimal('elevation_meters', 8, 2)->nullable();
            $table->text('current_crops')->nullable();
            $table->year('established_year')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['farmer_id', 'is_active']);
            $table->index(['location_barangay_id']);
            $table->index(['display_latitude', 'display_longitude']);
            $table->index(['farm_type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farms');
    }
};
