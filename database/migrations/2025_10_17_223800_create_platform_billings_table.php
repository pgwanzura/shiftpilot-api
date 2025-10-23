<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up()
    {
        Schema::create('platform_billing', function (Blueprint $table) {
            $table->id();
            $table->decimal('commission_rate', 5, 2)->default(2.00);
            $table->decimal('transaction_fee_flat', 8, 2)->default(0.30);
            $table->decimal('transaction_fee_percent', 5, 2)->default(2.90);
            $table->integer('payout_schedule_days')->default(7);
            $table->decimal('tax_vat_rate_percent', 5, 2)->default(0.00);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('platform_billing');
    }
};
