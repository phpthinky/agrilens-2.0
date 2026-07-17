<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('soil_samples', function (Blueprint $table) {
            $table->foreignId('farm_id')->nullable()->after('farmer_id')->constrained('farms')->nullOnDelete();
            $table->enum('analysis_type', ['manual', 'colorimetric', 'probe'])->default('colorimetric')->after('farm_id');
            $table->string('soil_type', 100)->nullable()->after('analysis_type');
            $table->string('probe_id', 100)->nullable()->after('soil_type');
            $table->json('probe_raw_payload')->nullable()->after('probe_id');
        });
    }

    public function down(): void
    {
        Schema::table('soil_samples', function (Blueprint $table) {
            $table->dropForeign(['farm_id']);
            $table->dropColumn(['farm_id', 'analysis_type', 'soil_type', 'probe_id', 'probe_raw_payload']);
        });
    }
};
