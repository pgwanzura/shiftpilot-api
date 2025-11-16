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
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('recurrence_type')->default('weekly');
            $table->json('recurrence_rules')->nullable();
            $table->string('timezone')->default('UTC');
            $table->string('status')->default('active');
            $table->date('effective_start_date')->nullable();
            $table->date('effective_end_date')->nullable();
            $table->integer('max_occurrences')->nullable();
            $table->boolean('auto_publish')->default(false);
            $table->integer('generation_count')->default(0);
            $table->date('last_generated_date')->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('created_by_id')->constrained('users');
            $table->timestamps();

            $table->index(['assignment_id', 'day_of_week']);
            $table->index(['effective_start_date', 'effective_end_date']);
            $table->index(['status']);
            $table->index(['last_generated_date']);
            $table->index(['created_by_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('shift_templates');
    }
};
