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
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->foreignId('submitted_by_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->unique(['shift_request_id', 'agency_id']);
            $table->index(['status']);
            $table->index(['agency_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agency_responses');
    }
};
