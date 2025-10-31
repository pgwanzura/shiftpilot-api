<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up()
    {
        Schema::create('placements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employer_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('role_requirements');
            $table->json('required_qualifications')->nullable();
            $table->enum('experience_level', ['entry', 'intermediate', 'senior'])->default('entry');
            $table->boolean('background_check_required')->default(false);
            $table->foreignId('location_id')->constrained()->onDelete('cascade');
            $table->text('location_instructions')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('shift_pattern', ['one_time', 'recurring', 'ongoing'])->default('one_time');
            $table->json('recurrence_rules')->nullable();
            $table->enum('budget_type', ['hourly', 'daily', 'fixed'])->default('hourly');
            $table->decimal('budget_amount', 10, 2);
            $table->string('currency', 3)->default('GBP');
            $table->json('overtime_rules')->nullable();
            $table->enum('target_agencies', ['all', 'specific'])->default('all');
            $table->json('specific_agency_ids')->nullable();
            $table->timestamp('response_deadline')->nullable();
            $table->enum('status', ['draft', 'active', 'filled', 'cancelled', 'completed'])->default('draft');
            $table->foreignId('selected_agency_id')->nullable()->constrained('agencies')->onDelete('cascade');
            $table->foreignId('selected_employee_id')->nullable()->constrained('employees')->onDelete('cascade');
            $table->decimal('agreed_rate', 10, 2)->nullable();
            $table->foreignId('created_by_id')->constrained('users')->onDelete('cascade');

            $table->timestamps();

            $table->index(['employer_id', 'status']);
            $table->index(['status', 'response_deadline']);
            $table->index(['target_agencies', 'status']);
            $table->index('created_by_id');
            $table->index('location_id');
            $table->index('start_date');
            $table->index('selected_agency_id');
            $table->index('selected_employee_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('placements');
    }
};
