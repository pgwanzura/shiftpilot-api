<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employer_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('role')->default('manager');
            $table->boolean('can_approve_timesheets')->default(true);
            $table->boolean('can_approve_assignments')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['employer_id', 'user_id']);
            $table->index(['employer_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
