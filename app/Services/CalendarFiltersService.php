<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CalendarFiltersService
{
    public function applyFilters(Builder $query, array $filters, string $userRole): Builder
    {
        $this->validateFilters($filters, $userRole);

        $filterMap = $this->getFilterMap($userRole);

        foreach ($filterMap as $key => $filter) {
            if ($this->shouldApplyFilter($filters, $key)) {
                $filter($query, $filters[$key]);
            }
        }

        return $query;
    }

    private function getFilterMap(string $userRole): array
    {
        $baseFilters = [
            'entity_type' => function (Builder $query, $value) {
                return $value && $value !== 'all'
                    ? $query->where('entity_type', $value)
                    : $query;
            },
            'status' => function (Builder $query, $value) {
                return is_array($value) && !empty($value)
                    ? $query->whereIn('status', $value)
                    : $query;
            },
            'priority' => function (Builder $query, $value) {
                return is_array($value) && !empty($value)
                    ? $query->whereIn('priority', $value)
                    : $query;
            },
            'requires_action' => function (Builder $query, $value) {
                return $query->where('requires_action', filter_var($value, FILTER_VALIDATE_BOOLEAN));
            },
            'date_range' => function (Builder $query, $value) {
                if (isset($value['start']) && isset($value['end'])) {
                    return $query->whereBetween('start_time', [
                        Carbon::parse($value['start']),
                        Carbon::parse($value['end'])
                    ]);
                }
                return $query;
            },
        ];

        $roleBasedFilters = $this->getRoleBasedFilterMap($userRole);

        return array_merge($baseFilters, $roleBasedFilters);
    }

    private function getRoleBasedFilterMap(string $userRole): array
    {
        return match ($userRole) {
            'agency_admin', 'agent' => [
                'agency_id' => function (Builder $query, $value) {
                    return $query->where('agency_id', $value);
                },
                'employer_id' => function (Builder $query, $value) {
                    return is_array($value)
                        ? $query->whereIn('employer_id', $value)
                        : $query->where('employer_id', $value);
                },
            ],
            'employer_admin', 'contact' => [
                'employer_id' => function (Builder $query, $value) {
                    return $query->where('employer_id', $value);
                },
                'location_id' => function (Builder $query, $value) {
                    return is_array($value)
                        ? $query->whereIn('location_id', $value)
                        : $query->where('location_id', $value);
                },
            ],
            'employee' => [
                'employee_id' => function (Builder $query, $value) {
                    return $query->where('employee_id', $value);
                },
            ],
            'super_admin' => [
                'agency_id' => function (Builder $query, $value) {
                    return is_array($value)
                        ? $query->whereIn('agency_id', $value)
                        : $query->where('agency_id', $value);
                },
                'employer_id' => function (Builder $query, $value) {
                    return is_array($value)
                        ? $query->whereIn('employer_id', $value)
                        : $query->where('employer_id', $value);
                },
            ],
            default => []
        };
    }

    private function shouldApplyFilter(array $filters, string $key): bool
    {
        if (!isset($filters[$key])) {
            return false;
        }

        $value = $filters[$key];

        if (is_array($value) && empty($value)) {
            return false;
        }

        if ($value === null || $value === '') {
            return false;
        }

        return true;
    }

    private function validateFilters(array $filters, string $userRole): void
    {
        $allowedFilters = array_keys($this->getFilterMap($userRole));

        foreach (array_keys($filters) as $filterKey) {
            if (!in_array($filterKey, $allowedFilters)) {
                throw new \InvalidArgumentException("Invalid filter key: {$filterKey} for role: {$userRole}");
            }
        }
    }

    public function getRoleBasedConfig(string $userRole, Request $request): array
    {
        return [
            'userRole' => $userRole,
            'dataScope' => $this->resolveDataScope($userRole),
            'permittedActions' => $this->resolvePermittedActions($userRole),
            'defaultView' => $this->resolveDefaultView($userRole),
            'entityFilters' => $this->buildEntityFilters($userRole, $request),
        ];
    }

    private function resolveDataScope(string $userRole): string
    {
        return match ($userRole) {
            'super_admin' => 'global',
            'agency_admin', 'agent' => 'agency',
            'employer_admin', 'contact' => 'employer',
            'employee' => 'personal',
            default => throw new \InvalidArgumentException("Unknown user role: {$userRole}")
        };
    }

    private function resolvePermittedActions(string $userRole): array
    {
        $actions = match ($userRole) {
            'super_admin' => ['assign', 'offer', 'accept', 'reject', 'approve', 'complete', 'clock_in', 'clock_out', 'request_time_off', 'manage'],
            'agency_admin' => ['assign', 'offer', 'approve', 'complete', 'view_reports'],
            'agent' => ['assign', 'offer', 'view_schedule'],
            'employer_admin' => ['approve', 'complete', 'create_shift', 'view_budget'],
            'contact' => ['approve', 'view_schedule'],
            'employee' => ['accept', 'reject', 'clock_in', 'clock_out', 'request_time_off', 'set_availability'],
            default => throw new \InvalidArgumentException("Unknown user role: {$userRole}")
        };

        return array_values($actions);
    }

    private function resolveDefaultView(string $userRole): string
    {
        return match ($userRole) {
            'agency_admin', 'agent' => 'schedule',
            'employer_admin' => 'month',
            'contact' => 'week',
            'employee' => 'day',
            'super_admin' => 'month',
            default => throw new \InvalidArgumentException("Unknown user role: {$userRole}")
        };
    }

    private function buildEntityFilters(string $userRole, Request $request): array
    {
        $startDate = $this->parseDateFromRequest($request, 'start_date', now()->startOfMonth());
        $endDate = $this->parseDateFromRequest($request, 'end_date', now()->addMonths(2)->endOfMonth());

        $baseFilters = [
            'dateRange' => [
                'start' => $startDate->toISOString(),
                'end' => $endDate->toISOString(),
            ],
            'view' => $request->get('view', $this->resolveDefaultView($userRole)),
            'entities' => $this->parseArrayParameter($request, 'entities'),
        ];

        $roleSpecificFilters = $this->buildRoleSpecificFilters($userRole, $request);

        return array_merge($baseFilters, $roleSpecificFilters);
    }

    private function buildRoleSpecificFilters(string $userRole, Request $request): array
    {
        $defaults = $this->getRoleFilterDefaults($userRole);

        return [
            'status' => $this->parseArrayParameter($request, 'status', $defaults['status']),
            'type' => $this->parseArrayParameter($request, 'type', $defaults['type']),
            'priority' => $this->parseArrayParameter($request, 'priority', $defaults['priority']),
            'requiresAction' => $request->boolean('requires_action', $defaults['requiresAction']),
        ];
    }

    private function getRoleFilterDefaults(string $userRole): array
    {
        return match ($userRole) {
            'agency_admin', 'agent' => [
                'status' => ['open', 'offered', 'assigned', 'completed'],
                'type' => ['shift', 'placement', 'availability'],
                'priority' => [], // Show all by default
                'requiresAction' => false, // Show all by default
            ],
            'employer_admin', 'contact' => [
                'status' => ['assigned', 'completed', 'agency_approved'],
                'type' => ['shift', 'placement'],
                'priority' => [], // Show all by default
                'requiresAction' => false, // Show all by default
            ],
            'employee' => [
                'status' => ['offered', 'assigned', 'completed'],
                'type' => ['shift', 'time_off', 'availability'],
                'priority' => [], // Show all by default
                'requiresAction' => false, // Show all by default
            ],
            'super_admin' => [
                'status' => [], // Show all by default
                'type' => [], // Show all by default
                'priority' => [], // Show all by default
                'requiresAction' => false, // Show all by default
            ],
            default => throw new \InvalidArgumentException("Unknown user role: {$userRole}")
        };
    }

    private function parseDateFromRequest(Request $request, string $key, Carbon $default): Carbon
    {
        $dateString = $request->get($key);

        if (!$dateString) {
            return $default;
        }

        try {
            return $key === 'start_date'
                ? Carbon::parse($dateString)->startOfDay()
                : Carbon::parse($dateString)->endOfDay();
        } catch (\Exception $e) {
            return $default;
        }
    }

    private function parseArrayParameter(Request $request, string $key, array $default = []): array
    {
        $value = $request->get($key);

        if (is_array($value)) {
            return array_filter($value); // Remove empty values
        }

        if (is_string($value) && $value !== '') {
            return explode(',', $value);
        }

        return $default;
    }

    public function getAvailableFilters(string $userRole): array
    {
        return [
            'date_range' => ['type' => 'date_range', 'label' => 'Date Range'],
            'entity_type' => ['type' => 'select', 'label' => 'Entity Type'],
            'status' => ['type' => 'multi_select', 'label' => 'Status'],
            'priority' => ['type' => 'multi_select', 'label' => 'Priority'],
            'requires_action' => ['type' => 'boolean', 'label' => 'Requires Action'],
            ...$this->getRoleBasedAvailableFilters($userRole),
        ];
    }

    private function getRoleBasedAvailableFilters(string $userRole): array
    {
        return match ($userRole) {
            'agency_admin', 'agent', 'super_admin' => [
                'agency_id' => ['type' => 'select', 'label' => 'Agency'],
                'employer_id' => ['type' => 'multi_select', 'label' => 'Employer'],
            ],
            'employer_admin', 'contact' => [
                'employer_id' => ['type' => 'select', 'label' => 'Employer'],
                'location_id' => ['type' => 'multi_select', 'label' => 'Location'],
            ],
            'employee' => [
                'employee_id' => ['type' => 'select', 'label' => 'Employee'],
            ],
            default => []
        };
    }
}
