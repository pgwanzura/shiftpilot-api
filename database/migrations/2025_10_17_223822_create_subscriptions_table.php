<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up()
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type'); // employer, agency
            $table->unsignedBigInteger('entity_id');
            $table->string('plan_key'); // agency_pro, employer_basic
            $table->string('plan_name');
            $table->decimal('amount', 8, 2);
            $table->string('interval')->default('monthly'); // monthly, yearly
            $table->string('status')->default('active'); // active, past_due, cancelled
            $table->timestamp('started_at');
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('subscriptions');
    }
};
