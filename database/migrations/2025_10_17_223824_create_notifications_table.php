<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('recipient_type'); // user, agency, employer
            $table->unsignedBigInteger('recipient_id');
            $table->string('channel')->default('in_app'); // email, sms, in_app
            $table->string('template_key');
            $table->json('payload')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['recipient_type', 'recipient_id']);
            $table->index(['is_read']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('notifications');
    }
};
