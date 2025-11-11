<?php

namespace App\Events\Assignment;

use App\Models\Assignment;
use App\Enums\AssignmentStatus;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AssignmentStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Assignment $assignment,
        public AssignmentStatus $fromStatus,
        public AssignmentStatus $toStatus
    ) {}

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
        return 'assignment.status.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'assignment_id' => $this->assignment->id,
            'from_status' => $this->fromStatus->value,
            'from_status_label' => $this->fromStatus->label(),
            'to_status' => $this->toStatus->value,
            'to_status_label' => $this->toStatus->label(),
            'role' => $this->assignment->role,
            'agency_name' => $this->assignment->agencyEmployee->agency->name,
            'employer_name' => $this->assignment->contract->employer->name,
            'employee_name' => $this->assignment->agencyEmployee->employee->user->name,
        ];
    }
}
