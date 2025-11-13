<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('employee_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->json('preferred_shift_types')->nullable();
            $table->json('preferred_locations')->nullable();
            $table->json('preferred_industries')->nullable();
            $table->json('preferred_roles')->nullable();
            $table->integer('max_travel_distance_km')->nullable();
            $table->decimal('min_hourly_rate', 8, 2)->nullable();
            $table->json('preferred_shift_lengths')->nullable();
            $table->json('preferred_days')->nullable();
            $table->json('preferred_start_times')->nullable();
            $table->json('preferred_employment_types')->nullable();
            $table->json('notification_preferences')->nullable();
            $table->json('communication_preferences')->nullable();
            $table->boolean('auto_accept_offers')->default(false);
            $table->integer('max_shifts_per_week')->nullable();
            $table->timestamps();

            $table->unique(['employee_id']);
            $table->index(['max_travel_distance_km']);
            $table->index(['min_hourly_rate']);
            $table->index(['auto_accept_offers']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('employee_preferences');
    }
};