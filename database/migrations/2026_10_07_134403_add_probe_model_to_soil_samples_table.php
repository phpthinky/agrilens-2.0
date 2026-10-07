<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('soil_samples', function (Blueprint $table) {
            $table->string('probe_model', 100)->nullable()->after('probe_id')
                ->comment('Handheld/RS485 probe model used, e.g. "Yago 8-in-1"');
        });
    }

    public function down(): void
    {
        Schema::table('soil_samples', function (Blueprint $table) {
            $table->dropColumn('probe_model');
        });
    }
};
