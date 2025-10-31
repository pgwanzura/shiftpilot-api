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
        Schema::create('agency_placement_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('placement_id')->constrained()->onDelete('cascade');
            $table->foreignId('agency_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['draft', 'submitted', 'accepted', 'rejected', 'withdrawn'])->default('draft');
            $table->json('submitted_employees')->nullable();
            $table->json('employer_feedback')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->unique(['placement_id', 'agency_id']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agency_placement_responses');
    }
};
