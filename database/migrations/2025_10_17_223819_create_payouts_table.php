<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->onDelete('cascade');
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('total_amount', 12, 2);
            $table->integer('employee_count');
            $table->enum('status', ['processing', 'paid', 'failed', 'cancelled'])->default('processing');
            $table->string('provider_payout_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['agency_id', 'period_start']);
            $table->index(['status', 'period_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};
