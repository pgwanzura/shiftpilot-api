<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up()
    {
        Schema::create('shift_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained()->onDelete('cascade');
            $table->foreignId('contact_id')->constrained()->onDelete('cascade');
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->timestamp('signed_at')->nullable();
            $table->string('signature_blob_url')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['shift_id', 'contact_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('shift_approvals');
    }
};
