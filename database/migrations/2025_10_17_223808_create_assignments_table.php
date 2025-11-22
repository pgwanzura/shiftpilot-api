<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('employer_agency_contracts');
            $table->foreignId('agency_employee_id')->constrained('agency_employees');
            $table->foreignId('shift_request_id')->nullable()->constrained('shift_requests');
            $table->foreignId('agency_response_id')->nullable()->constrained('agency_responses');
            $table->foreignId('location_id')->constrained('locations');
            $table->string('role');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->integer('expected_hours_per_week')->nullable();
            $table->decimal('agreed_rate', 10, 2);
            $table->decimal('pay_rate', 8, 2);
            $table->decimal('markup_amount', 10, 2);
            $table->decimal('markup_percent', 5, 2);
            $table->string('status')->default('active');
            $table->string('assignment_type')->default('direct');
            $table->json('shift_pattern')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by_id')->constrained('users');
            $table->timestamps();

            $table->index(['agency_employee_id', 'status']);
            $table->index(['contract_id', 'status']);
            $table->index(['status', 'start_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('assignments');
    }
};




// return new class extends Migration
// {
//     public function up(): void
//     {
//         Schema::create('assignments', function (Blueprint $table) {
//             $table->id();
//             $table->foreignId('contract_id')
//                 ->constrained('employer_agency_contracts')
//                 ->cascadeOnDelete();
//             $table->foreignId('agency_employee_id')
//                 ->constrained('agency_employees')
//                 ->cascadeOnDelete();
//             $table->foreignId('shift_request_id')
//                 ->nullable()
//                 ->constrained('shift_requests')
//                 ->nullOnDelete();
//             $table->foreignId('agency_response_id')
//                 ->nullable()
//                 ->constrained('agency_responses')
//                 ->nullOnDelete();
//             $table->foreignId('location_id')
//                 ->nullable()
//                 ->constrained('locations')
//                 ->nullOnDelete();
//             $table->foreignId('created_by_id')
//                 ->constrained('users')
//                 ->cascadeOnDelete();

//             $table->string('role');
//             $table->date('start_date');
//             $table->date('end_date')->nullable();
//             $table->decimal('expected_hours_per_week', 8, 2)->nullable();
//             $table->decimal('agreed_rate', 10, 2);
//             $table->decimal('pay_rate', 10, 2);
//             $table->decimal('markup_amount', 10, 2);
//             $table->decimal('markup_percent', 5, 2);
            
//             $table->string('status')->default('pending');
//             $table->string('assignment_type')->default('standard');
            
//             $table->json('shift_pattern')->nullable();
//             $table->text('notes')->nullable();
            
//             $table->timestamps();

//             $table->index(['agency_employee_id', 'status']);
//             $table->index(['contract_id', 'status']);
//             $table->index(['start_date', 'end_date']);
//             $table->index('status');
//             $table->index('assignment_type');
//         });
//     }

//     public function down(): void
//     {
//         Schema::dropIfExists('assignments');
//     }
// };