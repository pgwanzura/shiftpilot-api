<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up()
    {
        Schema::create('shift_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employer_id')->constrained()->onDelete('cascade');
            $table->foreignId('location_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('day_of_week'); // mon, tue, wed, etc.
            $table->time('start_time');
            $table->time('end_time');
            $table->string('role_requirement')->nullable();
            $table->json('required_qualifications')->nullable();
            $table->decimal('hourly_rate', 8, 2)->nullable();
            $table->string('recurrence_type')->default('weekly'); // weekly, biweekly, monthly
            $table->string('status')->default('active');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('created_by_type'); // employer, agency
            $table->unsignedBigInteger('created_by_id');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['employer_id', 'day_of_week']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('shift_templates');
    }
};
