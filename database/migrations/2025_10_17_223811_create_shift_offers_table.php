<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up()
    {
        Schema::create('shift_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained()->onDelete('cascade');
            $table->foreignId('agency_employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('agency_id')->constrained()->onDelete('cascade');
            $table->foreignId('agent_id')->constrained()->onDelete('cascade');
            $table->string('status')->default('pending');
            $table->timestamp('expires_at');
            $table->timestamp('responded_at')->nullable();
            $table->text('response_notes')->nullable();
            $table->timestamps();

            $table->unique(['shift_id', 'agency_employee_id']);
            $table->index(['agency_id', 'status']);
            $table->index(['agent_id']);
            $table->index(['expires_at']);
            $table->index(['agency_employee_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('shift_offers');
    }
};
