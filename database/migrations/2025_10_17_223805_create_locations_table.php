<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employer_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('city')->nullable();
            $table->string('county')->nullable();
            $table->string('postcode')->nullable();
            $table->string('country', 2)->nullable()->default('GB');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('location_type')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_phone')->nullable();
            $table->text('instructions')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['employer_id', 'name']);
            $table->index(['postcode']);
            $table->index(['city']);
            $table->index(['country']);
            $table->index(['latitude', 'longitude']);
            $table->index(['location_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
