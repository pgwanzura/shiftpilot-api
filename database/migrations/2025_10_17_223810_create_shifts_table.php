<?php
// database/migrations/2024_01_01_000001_create_shifts_table.php

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
            $table->date('shift_date');
            $table->timestamp('start_time');
            $table->timestamp('end_time');
            $table->decimal('hourly_rate', 8, 2);
            $table->string('status')->default('scheduled');
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['assignment_id', 'start_time', 'end_time']);
            $table->index(['start_time', 'end_time']);
            $table->index(['shift_date', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('shifts');
    }
};
