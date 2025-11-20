<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('agency_assignment_responses', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->foreignId('assignment_id')
                ->constrained('assignments')
                ->onDelete('cascade');

            $table->foreignId('agency_id')
                ->constrained('agencies')
                ->onDelete('cascade');

            // Proposal details
            $table->text('proposal_text');
            $table->decimal('proposed_rate', 10, 2);
            $table->integer('estimated_hours');

            // Status tracking
            $table->enum('status', ['submitted', 'reviewed', 'accepted', 'rejected'])
                ->default('submitted');

            $table->text('rejection_reason')->nullable();

            // Timestamps
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->unique(['assignment_id', 'agency_id']);
            $table->index(['agency_id', 'status']);
            $table->index(['assignment_id', 'status']);
            $table->index('submitted_at');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agency_assignment_responses');
    }
};
