<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up()
    {
        Schema::create('rate_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employer_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('agency_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('role_key'); // nurse, chef, driver
            $table->foreignId('location_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('day_of_week')->nullable(); // Mon, Tue, etc.
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->decimal('rate', 8, 2);
            $table->string('currency')->default('USD');
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->timestamps();

            $table->index(['employer_id', 'role_key']);
            $table->index(['agency_id', 'role_key']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('rate_cards');
    }
};
