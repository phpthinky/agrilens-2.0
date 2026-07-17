<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farmers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barangay_id')->nullable()->constrained()->nullOnDelete();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('suffix')->nullable();
            $table->enum('gender', ['Male', 'Female', 'Other']);
            $table->date('birth_date')->nullable();
            $table->string('contact_number')->nullable();
            $table->string('email')->nullable();
            $table->text('address');
            $table->string('id_type')->nullable();
            $table->string('id_number')->nullable();
            $table->enum('farmer_type', ['Owner', 'Tenant', 'Caretaker', 'Other'])->default('Owner');
            $table->integer('years_farming')->nullable();
            $table->text('crops_grown')->nullable();
            $table->decimal('total_farm_area', 8, 2)->nullable();
            $table->enum('education_level', [
                'Elementary',
                'Elementary Graduate',
                'High School',
                'High School Graduate',
                'Vocational',
                'College',
                'College Graduate',
                'Post Graduate',
            ])->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['barangay_id', 'is_active']);
            $table->index(['last_name', 'first_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farmers');
    }
};
