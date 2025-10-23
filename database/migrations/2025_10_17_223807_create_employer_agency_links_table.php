<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up()
    {
        Schema::create('employer_agency_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employer_id')->constrained()->onDelete('cascade');
            $table->foreignId('agency_id')->constrained()->onDelete('cascade');
            $table->string('status')->default('pending'); // pending, approved, suspended, terminated
            $table->string('contract_document_url')->nullable();
            $table->date('contract_start')->nullable();
            $table->date('contract_end')->nullable();
            $table->text('terms')->nullable();
            $table->timestamps();

            $table->unique(['employer_id', 'agency_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('employer_agency_links');
    }
};
