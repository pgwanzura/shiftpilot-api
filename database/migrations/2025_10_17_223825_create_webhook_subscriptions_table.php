<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up()
    {
        Schema::create('webhook_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('owner_type'); // agency, employer
            $table->unsignedBigInteger('owner_id');
            $table->string('url');
            $table->json('events');
            $table->string('secret');
            $table->string('status')->default('active');
            $table->timestamp('last_delivery_at')->nullable();
            $table->timestamps();

            $table->index(['owner_type', 'owner_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('webhook_subscriptions');
    }
};
