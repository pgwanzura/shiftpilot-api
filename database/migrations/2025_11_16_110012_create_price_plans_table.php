<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_plans', function (Blueprint $table) {
            $table->id();
            $table->string('plan_key')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('base_amount', 8, 2);
            $table->string('billing_interval')->default('monthly');
            $table->json('features')->nullable();
            $table->json('limits')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
            $table->index(['billing_interval']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_plans');
    }
};
