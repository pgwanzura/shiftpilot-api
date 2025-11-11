<?php

namespace App\Events\Assignment;

use App\Models\Assignment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AssignmentCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Assignment $assignment) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('assignments.' . $this->assignment->id),
            new PrivateChannel('agency.' . $this->assignment->agencyEmployee->agency_id),
            new PrivateChannel('employer.' . $this->assignment->contract->employer_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'assignment.created';
    }

    public function broadcastWith(): array
    {
        return [
            'assignment_id' => $this->assignment->id,
            'role' => $this->assignment->role,
            'start_date' => $this->assignment->start_date,
            'agency_name' => $this->assignment->agencyEmployee->agency->name,
            'employer_name' => $this->assignment->contract->employer->name,
            'employee_name' => $this->assignment->agencyEmployee->employee->user->name,
        ];
    }
}
