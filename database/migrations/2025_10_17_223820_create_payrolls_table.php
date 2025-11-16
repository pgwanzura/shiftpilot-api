<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_employee_id')->constrained()->onDelete('cascade');
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('total_hours', 8, 2);
            $table->decimal('gross_pay', 10, 2);
            $table->decimal('taxes', 10, 2)->default(0);
            $table->decimal('deductions', 10, 2)->default(0);
            $table->decimal('net_pay', 10, 2);
            $table->enum('status', ['draft', 'pending', 'processing', 'paid', 'failed', 'cancelled'])->default('draft');
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('payout_id')->nullable()->constrained()->onDelete('set null');
            $table->string('payment_reference')->nullable();
            $table->json('breakdown')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['agency_employee_id', 'period_start']);
            $table->index(['status', 'period_start']);
            $table->index(['paid_at']);
            $table->unique(['agency_employee_id', 'period_start', 'period_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
