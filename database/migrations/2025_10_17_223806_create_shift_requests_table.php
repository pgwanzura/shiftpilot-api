<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employer_id')->constrained()->onDelete('cascade');
            $table->foreignId('location_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('role');
            $table->json('required_qualifications')->nullable();
            $table->string('experience_level')->default('entry');
            $table->boolean('background_check_required')->default(false);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('shift_pattern')->default('one_time');
            $table->json('recurrence_rules')->nullable();
            $table->decimal('max_hourly_rate', 10, 2);
            $table->string('currency')->default('GBP');
            $table->integer('number_of_workers')->default(1);
            $table->string('target_agencies')->default('all');
            $table->json('specific_agency_ids')->nullable();
            $table->timestamp('response_deadline')->nullable();
            $table->string('status')->default('draft');
            $table->foreignId('created_by_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->index(['employer_id', 'status']);
            $table->index(['location_id']);
            $table->index(['start_date', 'end_date']);
            $table->index(['status', 'response_deadline']);
            $table->index(['target_agencies']);
            $table->index(['max_hourly_rate']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_requests');
    }
};