<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up()
    {
        Schema::create('employee_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->string('type')->default('recurring'); // recurring, one_time
            $table->string('day_of_week')->nullable(); // mon, tue, wed, etc.
            $table->date('start_date')->nullable(); // for one_time type
            $table->date('end_date')->nullable(); // for one_time type
            $table->time('start_time');
            $table->time('end_time');
            $table->string('timezone')->default('UTC');
            $table->string('status')->default('available'); // available, unavailable, preferred
            $table->integer('priority')->default(1);
            $table->json('location_preference')->nullable();
            $table->integer('max_shift_length_hours')->nullable();
            $table->integer('min_shift_length_hours')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'type']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('employee_availabilities');
    }
};
