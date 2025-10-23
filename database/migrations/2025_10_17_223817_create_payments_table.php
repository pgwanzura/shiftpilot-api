<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->string('payer_type'); // agency, employer, shiftpilot
            $table->unsignedBigInteger('payer_id');
            $table->decimal('amount', 12, 2);
            $table->string('method'); // stripe, bacs, sepa, paypal
            $table->string('processor_id')->nullable(); // stripe payment id
            $table->string('status')->default('completed'); // completed, failed, pending, refunded
            $table->decimal('fee_amount', 10, 2)->default(0.00);
            $table->decimal('net_amount', 12, 2);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['payer_type', 'payer_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('payments');
    }
};
