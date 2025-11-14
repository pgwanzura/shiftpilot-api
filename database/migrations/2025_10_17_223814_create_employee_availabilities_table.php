<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->integer('days_mask');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('type')->default('preferred');
            $table->integer('priority')->default(1);
            $table->integer('max_hours')->nullable();
            $table->boolean('flexible')->default(false);
            $table->json('constraints')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_availabilities');
    }
};
