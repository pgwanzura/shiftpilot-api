<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up()
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employer_id')->constrained()->onDelete('cascade');
            $table->foreignId('agency_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('placement_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('employee_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('agent_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('location_id')->constrained()->onDelete('cascade');
            $table->timestamp('start_time');
            $table->timestamp('end_time');
            $table->decimal('hourly_rate', 8, 2)->nullable();
            $table->string('status')->default('open'); // open, offered, assigned, completed, etc.
            $table->string('created_by_type'); // employer, agency
            $table->unsignedBigInteger('created_by_id');
            $table->json('meta')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['employer_id', 'start_time']);
            $table->index(['employee_id', 'start_time']);
            $table->index(['agency_id', 'start_time']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('shifts');
    }
};
