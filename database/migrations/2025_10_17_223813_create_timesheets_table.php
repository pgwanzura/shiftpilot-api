<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up()
    {
        Schema::create('timesheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained()->onDelete('cascade');
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->timestamp('clock_in')->nullable();
            $table->timestamp('clock_out')->nullable();
            $table->integer('break_minutes')->default(0);
            $table->decimal('hours_worked', 8, 2)->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('agency_approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('agency_approved_at')->nullable();
            $table->foreignId('approved_by_contact_id')->nullable()->constrained('contacts')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('attachments')->nullable();
            $table->timestamps();

            $table->unique(['shift_id', 'employee_id']);
            $table->index(['employee_id', 'status']);
            $table->index(['status', 'clock_in']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('timesheets');
    }
};
