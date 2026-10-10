<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE soil_test_assignments MODIFY status ENUM('pending', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE soil_test_assignments MODIFY status ENUM('pending', 'completed', 'cancelled') NOT NULL DEFAULT 'pending'");
    }
};
