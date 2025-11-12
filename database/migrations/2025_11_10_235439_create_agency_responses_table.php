<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agency_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_request_id')->constrained()->onDelete('cascade');
            $table->foreignId('agency_id')->constrained()->onDelete('cascade');
            $table->foreignId('proposed_employee_id')->nullable()->constrained('employees')->onDelete('set null');
            $table->decimal('proposed_rate', 10, 2);
            $table->date('proposed_start_date');
            $table->date('proposed_end_date')->nullable();
            $table->text('terms')->nullable();
            $table->integer('estimated_total_hours')->nullable();
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->foreignId('submitted_by_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('employer_decision_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('employer_decision_at')->nullable();
            $table->timestamps();

            $table->unique(['shift_request_id', 'agency_id']);
            $table->index(['status']);
            $table->index(['agency_id', 'created_at']);
            $table->index(['proposed_employee_id', 'status']);
            $table->index(['proposed_start_date', 'proposed_end_date']);
            $table->index(['employer_decision_by_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agency_responses');
    }
};
