<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Shift;
use App\Models\Placement;
use App\Models\TimeOffRequest;
use App\Models\EmployeeAvailability;
use App\Models\ShiftOffer;

class CalendarEventResource extends JsonResource
{
    public function toArray($request)
    {
        return array_merge($this->buildBaseData(), $this->formatEventData());
    }

    private function buildBaseData(): array
    {
        return [
            'id' => $this->generateEventId(),
            'title' => $this->resolveEventTitle(),
            'date' => $this->resolveEventDate(),
            'startTime' => $this->resolveStartTime(),
            'endTime' => $this->resolveEndTime(),
            'type' => $this->resolveEventType(),
            'status' => $this->resolveEventStatus(),
            'entityType' => $this->resolveEntityType(),
            'entityId' => $this->resolveEntityId(),
            'priority' => $this->resolveEventPriority(),
            'visibleTo' => $this->resolveVisibleToRoles(),
            'actionableBy' => $this->resolveActionableByRoles(),
            'requiresAction' => $this->determineRequiresAction(),
            'actionRequiredBy' => $this->resolveActionRequiredBy(),
            'actionDeadline' => $this->resolveActionDeadline(),
            'agencyId' => $this->resolveAgencyId(),
            'employerId' => $this->resolveEmployerId(),
            'locationId' => $this->resolveLocationId(),
            'employeeId' => $this->resolveEmployeeId(),
            'placementId' => $this->resolvePlacementId(),
            'contactId' => $this->resolveContactId(),
            'budgetAmount' => $this->resolveBudgetAmount(),
            'actualCost' => $this->resolveActualCost(),
            'hourlyRate' => $this->resolveHourlyRate(),
            'commissionRate' => $this->resolveCommissionRate(),
            'locationName' => $this->resolveLocationName(),
            'employerName' => $this->resolveEmployerName(),
            'agencyName' => $this->resolveAgencyName(),
            'employeeName' => $this->resolveEmployeeName(),
            'qualifications' => $this->resolveQualifications(),
            'availableActions' => $this->resolveAvailableActions(),
        ];
    }

    private function formatEventData(): array
    {
        return match (get_class($this->resource)) {
            Shift::class => $this->buildShiftData(),
            Placement::class => $this->buildPlacementData(),
            TimeOffRequest::class => $this->buildTimeOffData(),
            EmployeeAvailability::class => $this->buildAvailabilityData(),
            ShiftOffer::class => $this->buildShiftOfferData(),
            default => []
        };
    }

    private function buildShiftData(): array
    {
        return [
            'location' => $this->location->name ?? 'Unknown Location',
            'role' => $this->placement->title ?? 'Shift',
            'employer' => $this->employer->name ?? 'Unknown Employer',
            'agency' => $this->agency->name ?? null,
            'employee' => $this->employee->user->name ?? null,
            'payRate' => $this->hourly_rate,
            'hours' => $this->calculateShiftHours(),
        ];
    }

    private function buildPlacementData(): array
    {
        return [
            'location' => $this->location->name ?? 'Unknown Location',
            'role' => $this->title,
            'employer' => $this->employer->name ?? 'Unknown Employer',
            'agency' => $this->selectedAgency->name ?? null,
            'employee' => $this->selectedEmployee->user->name ?? null,
            'payRate' => $this->agreed_rate ?? $this->budget_amount,
            'hours' => $this->calculatePlacementHours(),
        ];
    }

    private function buildTimeOffData(): array
    {
        return [
            'employee' => $this->employee->user->name ?? 'Unknown Employee',
            'type' => $this->type,
            'reason' => $this->reason,
        ];
    }

    private function buildAvailabilityData(): array
    {
        return [
            'employee' => $this->employee->user->name ?? 'Unknown Employee',
            'status' => $this->status,
        ];
    }

    private function buildShiftOfferData(): array
    {
        return [
            'location' => $this->shift->location->name ?? 'Unknown Location',
            'role' => $this->shift->placement->title ?? 'Shift Offer',
            'employer' => $this->shift->employer->name ?? 'Unknown Employer',
            'employee' => $this->employee->user->name ?? 'Unknown Employee',
            'status' => $this->status,
        ];
    }

    private function generateEventId(): string
    {
        return get_class($this->resource) . '-' . $this->resource->id;
    }

    private function resolveEventTitle(): string
    {
        return match (get_class($this->resource)) {
            Shift::class => $this->placement->title ?? 'Shift',
            Placement::class => $this->title,
            TimeOffRequest::class => $this->type . ' - ' . ($this->employee->user->name ?? 'Employee'),
            EmployeeAvailability::class => 'Availability - ' . ($this->employee->user->name ?? 'Employee'),
            ShiftOffer::class => 'Shift Offer - ' . ($this->shift->placement->title ?? 'Shift'),
            default => 'Event'
        };
    }

    private function resolveEventDate(): string
    {
        return match (get_class($this->resource)) {
            Shift::class => $this->start_time->toDateString(),
            Placement::class => $this->start_date->toDateString(),
            TimeOffRequest::class => $this->start_date->toDateString(),
            EmployeeAvailability::class => $this->start_date?->toDateString() ?? now()->toDateString(),
            ShiftOffer::class => $this->shift->start_time->toDateString(),
            default => now()->toDateString()
        };
    }

    private function resolveStartTime(): string
    {
        return match (get_class($this->resource)) {
            Shift::class => $this->start_time->format('H:i'),
            Placement::class => '09:00',
            TimeOffRequest::class => $this->start_time ?? '00:00',
            EmployeeAvailability::class => $this->start_time,
            ShiftOffer::class => $this->shift->start_time->format('H:i'),
            default => '00:00'
        };
    }

    private function resolveEndTime(): string
    {
        return match (get_class($this->resource)) {
            Shift::class => $this->end_time->format('H:i'),
            Placement::class => '17:00',
            TimeOffRequest::class => $this->end_time ?? '23:59',
            EmployeeAvailability::class => $this->end_time,
            ShiftOffer::class => $this->shift->end_time->format('H:i'),
            default => '23:59'
        };
    }

    private function resolveEventType(): string
    {
        return match (get_class($this->resource)) {
            Shift::class => 'shift',
            Placement::class => 'placement',
            TimeOffRequest::class => 'time_off',
            EmployeeAvailability::class => 'availability',
            ShiftOffer::class => 'interview',
            default => 'meeting'
        };
    }

    private function resolveEventStatus(): string
    {
        return $this->resource->status ?? 'scheduled';
    }

    private function resolveEntityType(): string
    {
        return match (get_class($this->resource)) {
            Shift::class, ShiftOffer::class => 'shift',
            Placement::class => 'placement',
            TimeOffRequest::class => 'time_off',
            EmployeeAvailability::class => 'availability',
            default => 'shift'
        };
    }

    private function resolveEntityId(): string
    {
        return (string) $this->resource->id;
    }

    private function resolveEventPriority(): string
    {
        return match (get_class($this->resource)) {
            Shift::class => $this->calculateShiftPriority(),
            Placement::class => 'medium',
            TimeOffRequest::class, EmployeeAvailability::class => 'low',
            ShiftOffer::class => 'high',
            default => 'medium'
        };
    }

    private function calculateShiftPriority(): string
    {
        if ($this->status === 'open' && $this->start_time->diffInHours(now()) < 24) {
            return 'urgent';
        }

        return in_array($this->status, ['offered', 'assigned']) ? 'high' : 'medium';
    }

    private function resolveVisibleToRoles(): array
    {
        $baseRoles = ['super_admin'];

        return match (get_class($this->resource)) {
            Shift::class => array_merge($baseRoles, ['agency_admin', 'agent', 'employer_admin', 'contact', 'employee']),
            Placement::class => array_merge($baseRoles, ['agency_admin', 'employer_admin']),
            TimeOffRequest::class, EmployeeAvailability::class => array_merge($baseRoles, ['agency_admin', 'employee']),
            ShiftOffer::class => array_merge($baseRoles, ['agency_admin', 'agent', 'employee']),
            default => $baseRoles
        };
    }

    private function resolveActionableByRoles(): array
    {
        return match (get_class($this->resource)) {
            Shift::class => $this->resolveShiftActionableRoles(),
            Placement::class => ['agency_admin', 'employer_admin'],
            TimeOffRequest::class => ['agency_admin'],
            EmployeeAvailability::class => ['employee', 'agency_admin'],
            ShiftOffer::class => ['employee', 'agency_admin'],
            default => []
        };
    }

    private function resolveShiftActionableRoles(): array
    {
        $roles = [];

        if ($this->status === 'open') {
            array_push($roles, 'agency_admin', 'agent');
        }

        if ($this->status === 'offered') {
            array_push($roles, 'employee', 'agency_admin');
        }

        if (in_array($this->status, ['assigned', 'completed'])) {
            array_push($roles, 'employee', 'agency_admin', 'employer_admin', 'contact');
        }

        return $roles;
    }

    private function determineRequiresAction(): bool
    {
        return match (get_class($this->resource)) {
            Shift::class => in_array($this->status, ['open', 'offered', 'completed']),
            TimeOffRequest::class, ShiftOffer::class => $this->status === 'pending',
            default => false
        };
    }

    private function resolveActionRequiredBy(): ?string
    {
        if (!$this->determineRequiresAction()) {
            return null;
        }

        return match (get_class($this->resource)) {
            Shift::class => $this->resolveShiftActionRequiredBy(),
            TimeOffRequest::class => 'agency_admin',
            ShiftOffer::class => 'employee',
            default => null
        };
    }

    private function resolveShiftActionRequiredBy(): ?string
    {
        return match ($this->status) {
            'open' => 'agency_admin',
            'offered' => 'employee',
            'completed' => 'agency_admin',
            default => null
        };
    }

    private function resolveActionDeadline(): ?string
    {
        if (!$this->determineRequiresAction()) {
            return null;
        }

        return match (get_class($this->resource)) {
            Shift::class => $this->start_time->subHours(2)->toISOString(),
            ShiftOffer::class => $this->expires_at?->toISOString(),
            default => null
        };
    }

    private function resolveAvailableActions(): array
    {
        $userRole = request()->user()->role;

        if (!in_array($userRole, $this->resolveActionableByRoles())) {
            return [];
        }

        return match (get_class($this->resource)) {
            Shift::class => $this->buildShiftActions($userRole),
            default => []
        };
    }

    private function buildShiftActions(string $userRole): array
    {
        $actions = [];

        if (in_array($userRole, ['agency_admin', 'agent'])) {
            if ($this->status === 'open') {
                $actions[] = $this->createAction('offer', 'Offer Shift', 'primary', true);
            }

            if (in_array($this->status, ['offered', 'assigned'])) {
                $actions[] = $this->createAction('cancel', 'Cancel Shift', 'danger', true);
            }
        }

        if ($userRole === 'employee') {
            if ($this->status === 'offered') {
                $actions[] = $this->createAction('accept', 'Accept Shift', 'primary', true);
                $actions[] = $this->createAction('reject', 'Reject Shift', 'secondary', true);
            }

            if ($this->status === 'assigned') {
                $actions[] = $this->createAction('clock_in', 'Clock In', 'primary', true);
                $actions[] = $this->createAction('clock_out', 'Clock Out', 'secondary', false);
            }
        }

        return $actions;
    }

    private function createAction(string $type, string $label, string $variant, bool $enabled): array
    {
        return [
            'type' => $type,
            'label' => $label,
            'enabled' => $enabled,
            'variant' => $variant
        ];
    }

    private function resolveAgencyId(): ?string
    {
        return match (get_class($this->resource)) {
            Shift::class => $this->agency_id ? (string) $this->agency_id : null,
            Placement::class => $this->selected_agency_id ? (string) $this->selected_agency_id : null,
            EmployeeAvailability::class, TimeOffRequest::class => $this->employee->agency_id ? (string) $this->employee->agency_id : null,
            default => null
        };
    }

    private function resolveEmployerId(): ?string
    {
        return match (get_class($this->resource)) {
            Shift::class, Placement::class => $this->employer_id ? (string) $this->employer_id : null,
            default => null
        };
    }

    private function resolveLocationId(): ?string
    {
        return match (get_class($this->resource)) {
            Shift::class, Placement::class => $this->location_id ? (string) $this->location_id : null,
            default => null
        };
    }

    private function resolveEmployeeId(): ?string
    {
        $employeeId = match (get_class($this->resource)) {
            Shift::class, TimeOffRequest::class, EmployeeAvailability::class, ShiftOffer::class => $this->employee_id,
            default => null
        };

        return $employeeId ? (string) $employeeId : null;
    }

    private function resolvePlacementId(): ?string
    {
        return match (get_class($this->resource)) {
            Shift::class => $this->placement_id ? (string) $this->placement_id : null,
            Placement::class => (string) $this->id,
            default => null
        };
    }

    private function resolveContactId(): ?string
    {
        return null;
    }

    private function resolveBudgetAmount(): ?float
    {
        return match (get_class($this->resource)) {
            Placement::class => $this->budget_amount ? (float) $this->budget_amount : null,
            default => null
        };
    }

    private function resolveActualCost(): ?float
    {
        return null;
    }

    private function resolveHourlyRate(): ?float
    {
        return match (get_class($this->resource)) {
            Shift::class => $this->hourly_rate ? (float) $this->hourly_rate : null,
            default => null
        };
    }

    private function resolveCommissionRate(): ?float
    {
        return match (get_class($this->resource)) {
            Shift::class => $this->agency->commission_rate ?? null,
            default => null
        };
    }

    private function resolveLocationName(): ?string
    {
        return match (get_class($this->resource)) {
            Shift::class, Placement::class => $this->location->name ?? null,
            default => null
        };
    }

    private function resolveEmployerName(): ?string
    {
        return match (get_class($this->resource)) {
            Shift::class, Placement::class => $this->employer->name ?? null,
            default => null
        };
    }

    private function resolveAgencyName(): ?string
    {
        return match (get_class($this->resource)) {
            Shift::class => $this->agency->name ?? null,
            Placement::class => $this->selectedAgency->name ?? null,
            default => null
        };
    }

    private function resolveEmployeeName(): ?string
    {
        return match (get_class($this->resource)) {
            Shift::class, TimeOffRequest::class, EmployeeAvailability::class, ShiftOffer::class => $this->employee->user->name ?? null,
            default => null
        };
    }

    private function resolveQualifications(): array
    {
        return match (get_class($this->resource)) {
            Shift::class => $this->placement->required_qualifications ?? [],
            Placement::class => $this->required_qualifications ?? [],
            default => []
        };
    }

    private function calculateShiftHours(): float
    {
        return $this->start_time->diffInHours($this->end_time);
    }

    private function calculatePlacementHours(): float
    {
        return 37.5;
    }
}
