<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('crop_fertilizer_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crop_id')->constrained('crops')->cascadeOnDelete();
            $table->tinyInteger('application_order')->comment('1=First, 2=Second, 3=Third');
            $table->string('application_label')->comment('e.g. First Application (0-14 DAT*)');
            $table->string('fertilizer_type', 20)->comment('14-14-14, 46-0-0, 21-0-0, 25-0-0, 16-20-0, 0-0-60');
            $table->decimal('bags', 5, 2)->default(0)->comment('Number of bags per hectare');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['crop_id', 'application_order', 'fertilizer_type'], 'crop_app_fert_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crop_fertilizer_schedules');
    }
};
