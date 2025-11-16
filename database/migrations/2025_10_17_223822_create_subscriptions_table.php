<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->onDelete('cascade');
            $table->string('plan_key');
            $table->string('plan_name');
            $table->decimal('amount', 8, 2);
            $table->enum('interval', ['monthly', 'yearly'])->default('monthly');
            $table->enum('status', ['active', 'past_due', 'cancelled', 'suspended'])->default('active');
            $table->timestamp('started_at');
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['agency_id', 'status']);
            $table->index(['status', 'current_period_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
