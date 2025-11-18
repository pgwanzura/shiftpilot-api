<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agency_branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('branch_code')->nullable()->unique();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('city')->nullable();
            $table->string('county')->nullable();
            $table->string('postcode')->nullable();
            $table->string('country', 2)->default('GB');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->boolean('is_head_office')->default(false);
            $table->string('status')->default('active');
            $table->json('opening_hours')->nullable();
            $table->json('services_offered')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['agency_id', 'name']);
            $table->index(['agency_id', 'is_head_office']);
            $table->index(['branch_code']);
            $table->index(['postcode']);
            $table->index(['city']);
            $table->index(['country']);
            $table->index(['latitude', 'longitude']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agency_branches');
    }
};
