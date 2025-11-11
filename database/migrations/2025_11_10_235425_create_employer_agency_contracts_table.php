<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employer_agency_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employer_id')->constrained()->onDelete('cascade');
            $table->foreignId('agency_id')->constrained()->onDelete('cascade');
            $table->string('status')->default('pending');
            $table->string('contract_document_url')->nullable();
            $table->date('contract_start')->nullable();
            $table->date('contract_end')->nullable();
            $table->text('terms')->nullable();
            $table->timestamps();

            $table->unique(['employer_id', 'agency_id']);
            $table->index(['status']);
            $table->index(['contract_start', 'contract_end']);
            $table->index(['employer_id', 'status']);
            $table->index(['agency_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employer_agency_contracts');
    }
};
