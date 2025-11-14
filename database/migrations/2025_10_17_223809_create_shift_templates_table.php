<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up()
    {
        Schema::create('shift_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('break_minutes')->default(0);
            $table->integer('required_employees')->default(1);
            $table->json('recurrence_pattern')->nullable();
            $table->date('effective_start_date')->nullable();
            $table->date('effective_end_date')->nullable();
            $table->date('last_generated_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['assignment_id', 'day_of_week']);
            $table->index(['effective_start_date', 'effective_end_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('shift_templates');
    }
};