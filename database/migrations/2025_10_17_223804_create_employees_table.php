<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('national_insurance_number')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->json('qualifications')->nullable();
            $table->json('certifications')->nullable();
            $table->string('status')->default('active');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id']);
            $table->index(['national_insurance_number']);
            $table->index(['status']);
            $table->index(['date_of_birth']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
