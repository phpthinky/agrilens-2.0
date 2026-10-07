<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Widen the enum first so both the old and new value are valid while
        // the data migrates, then narrow it back down once nothing uses
        // 'professional' anymore — MySQL rejects rows holding a value that
        // isn't in the enum at the moment of the ALTER.
        DB::statement("ALTER TABLE users MODIFY user_type ENUM('farmer','professional','admin','technician') DEFAULT 'farmer'");
        DB::table('users')->where('user_type', 'professional')->update(['user_type' => 'technician']);
        DB::statement("ALTER TABLE users MODIFY user_type ENUM('farmer','technician','admin') DEFAULT 'farmer'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY user_type ENUM('farmer','professional','admin','technician') DEFAULT 'farmer'");
        DB::table('users')->where('user_type', 'technician')->update(['user_type' => 'professional']);
        DB::statement("ALTER TABLE users MODIFY user_type ENUM('farmer','professional','admin') DEFAULT 'farmer'");
    }
};
