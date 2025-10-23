<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up()
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('agency_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('employer_id')->nullable()->constrained()->onDelete('set null');
            $table->string('position')->nullable();
            $table->decimal('pay_rate', 8, 2)->nullable();
            $table->json('availability')->nullable();
            $table->json('qualifications')->nullable();
            $table->string('employment_type')->default('temp');
            $table->string('status')->default('active');
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('employees');
    }
};
