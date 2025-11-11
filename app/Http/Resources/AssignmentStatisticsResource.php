<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssignmentStatisticsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'total_assignments' => $this['total'],
            'active_assignments' => $this['active'],
            'pending_assignments' => $this['pending'],
            'completed_assignments' => $this['completed'],
            'cancelled_assignments' => $this['cancelled'],
            'suspended_assignments' => $this['suspended'],

            'financial_summary' => $this['financial_summary'] ?? null,
            'utilization_rate' => $this['utilization_rate'] ?? null,
            'average_duration_days' => $this['average_duration_days'] ?? null,

            'period' => [
                'start_date' => $this['period_start'] ?? null,
                'end_date' => $this['period_end'] ?? null,
            ],
        ];
    }
}
