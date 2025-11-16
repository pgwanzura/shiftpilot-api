<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('assignments');
            $table->foreignId('location_id')->constrained('locations');
            $table->date('shift_date');
            $table->timestamp('start_time');
            $table->timestamp('end_time');
            $table->decimal('hourly_rate', 8, 2);
            $table->string('status')->default('scheduled');
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['assignment_id', 'shift_date']);
            $table->index(['shift_date', 'status']);
            $table->index(['location_id']);
            $table->index(['start_time', 'end_time']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('shifts');
    }
};
