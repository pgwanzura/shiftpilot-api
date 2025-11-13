<?php

namespace App\Events;

use App\Models\Employee;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmployeeAvailabilityUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $employee;

    public function __construct(Employee $employee)
    {
        $this->employee = $employee;
    }

    public function broadcastOn()
    {
        return [
            new PrivateChannel('employee.availability.' . $this->employee->id),
            new PrivateChannel('agency.employees.' . $this->employee->id),
        ];
    }

    public function broadcastAs()
    {
        return 'employee.availability.updated';
    }

    public function broadcastWith()
    {
        return [
            'employee_id' => $this->employee->id,
            'employee_name' => $this->employee->user->name,
            'updated_at' => now()->toISOString(),
        ];
    }
}
