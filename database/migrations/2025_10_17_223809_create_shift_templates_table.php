<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {

    public function up()
    {
        Schema::table('shift_templates', function (Blueprint $table) {
            $table->dropForeign(['employer_id']);
            $table->dropForeign(['location_id']);
            $table->dropColumn(['employer_id', 'location_id', 'role_requirement', 'required_qualifications', 'hourly_rate', 'start_date', 'end_date', 'created_by_type', 'created_by_id']);
            $table->foreignId('assignment_id')->constrained()->onDelete('cascade');
            $table->date('effective_start_date')->nullable();
            $table->date('effective_end_date')->nullable();
            $table->date('last_generated_date')->nullable();
        });
    }

    public function down()
    {
        Schema::table('shift_templates', function (Blueprint $table) {
            $table->dropForeign(['assignment_id']);
            $table->dropColumn(['assignment_id', 'effective_start_date', 'effective_end_date', 'last_generated_date']);

            $table->foreignId('employer_id')->constrained()->onDelete('cascade');
            $table->foreignId('location_id')->constrained()->onDelete('cascade');
            $table->string('role_requirement')->nullable();
            $table->json('required_qualifications')->nullable();
            $table->decimal('hourly_rate', 8, 2);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('created_by_type');
            $table->unsignedBigInteger('created_by_id');
        });
    }
};
