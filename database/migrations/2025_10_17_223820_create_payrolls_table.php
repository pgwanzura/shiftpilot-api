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
            $table->decimal('net_pay', 10, 2);
            $table->enum('status', ['pending', 'processing', 'paid', 'failed'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('payout_id')->nullable()->constrained()->onDelete('set null');
            $table->string('payment_reference')->nullable();
            $table->timestamps();

            $table->index(['agency_employee_id', 'period_start']);
            $table->index(['status', 'period_start']);
            $table->index(['paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
