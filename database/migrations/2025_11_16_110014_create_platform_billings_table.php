<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_billings', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->foreignId('agency_id')
                ->constrained('agencies')
                ->onDelete('cascade');

            $table->foreignId('subscription_id')
                ->nullable()
                ->constrained('subscriptions')
                ->onDelete('set null');

            // Billing details
            $table->string('billing_period'); // e.g., "2024-01", "2024-Q1"
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('subscription_fee', 10, 2)->default(0);
            $table->decimal('usage_fee', 10, 2)->default(0);
            $table->decimal('transaction_fee', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->string('currency')->default('GBP');

            // Status and payment
            $table->enum('status', ['draft', 'generated', 'sent', 'paid', 'overdue', 'cancelled'])
                ->default('draft');

            $table->timestamp('generated_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('due_date')->nullable();

            // Metadata
            $table->json('line_items')->nullable();
            $table->text('notes')->nullable();
            $table->string('invoice_number')->unique()->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['agency_id', 'billing_period']);
            $table->index(['status', 'due_date']);
            $table->index(['invoice_number']);
            $table->index(['period_start', 'period_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_billings');
    }
};
