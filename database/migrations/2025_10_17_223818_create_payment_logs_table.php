<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->decimal('amount_paid', 12, 2);
            $table->string('currency', 3)->default('GBP');
            $table->string('payment_method'); // stripe, bacs, sepa, paypal, cash
            $table->dateTime('payment_date');
            $table->string('reference')->nullable(); // bank reference, transaction id
            $table->text('notes')->nullable();
            $table->string('status')->default('pending_confirmation'); // pending_confirmation, confirmed, rejected
            $table->foreignId('logged_by_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('confirmed_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['status', 'payment_date']);
            $table->index('reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_logs');
    }
};
