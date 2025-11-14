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
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('offered_by_id')->constrained('users')->onDelete('cascade');
            $table->string('status')->default('pending');
            $table->timestamp('expires_at');
            $table->timestamp('responded_at')->nullable();
            $table->text('response_notes')->nullable();
            $table->timestamps();

            $table->unique(['shift_id', 'employee_id']);
            $table->index(['employee_id', 'status']);
            $table->index(['status', 'expires_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('shift_offers');
    }
};
