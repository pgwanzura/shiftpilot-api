<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up()
    {
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->onDelete('cascade');
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('total_hours', 8, 2);
            $table->decimal('gross_pay', 10, 2);
            $table->decimal('taxes', 10, 2)->default(0.00);
            $table->decimal('net_pay', 10, 2);
            $table->string('status')->default('unpaid'); // unpaid, paid
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('payout_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();

            $table->index(['agency_id', 'period_start', 'period_end']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('payrolls');
    }
};
