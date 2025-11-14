<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agency_employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->onDelete('cascade');
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->string('position')->nullable();
            $table->decimal('pay_rate', 8, 2);
            $table->enum('employment_type', ['temp', 'contract'])->default('temp');
            $table->enum('status', ['active', 'inactive', 'suspended', 'terminated'])->default('active');
            $table->date('contract_start_date')->nullable();
            $table->date('contract_end_date')->nullable();
            $table->json('specializations')->nullable();
            $table->json('preferred_locations')->nullable();
            $table->integer('max_weekly_hours')->nullable();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['agency_id', 'employee_id']);
            $table->index(['employee_id', 'status']);
            $table->index(['agency_id', 'status']);
            $table->index(['employment_type']);
            $table->index(['contract_start_date', 'contract_end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agency_employees');
    }
};
