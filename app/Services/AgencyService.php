<?php

namespace App\Services;

use App\Events\AgencyCreated;
use App\Events\Agency\AgencyStatusChanged;
use App\Events\AgencyUpdated;
use App\Events\Timesheet\TimesheetApproved;
use App\Models\Agency;
use App\Models\AgencyEmployee;
use App\Models\AgencyResponse;
use App\Models\Assignment;
use App\Models\Contact;
use App\Models\Employee;
use App\Models\Employer;
use App\Models\EmployerAgencyContract;
use App\Models\Invoice;
use App\Models\Payroll;
use App\Models\Payout;
use App\Models\Shift;
use App\Models\ShiftOffer;
use App\Models\ShiftRequest;
use App\Models\ShiftTemplate;
use App\Models\Subscription;
use App\Models\Timesheet;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AgencyService
{
    public function createAgency(array $data, User $user): Agency
    {
        return DB::transaction(function () use ($data, $user) {
            $agency = Agency::create(array_merge($data, ['user_id' => $user->id]));
            AgencyCreated::dispatch($agency);
            return $agency;
        });
    }

    public function updateAgency(Agency $agency, array $data): Agency
    {
        return DB::transaction(function () use ($agency, $data) {
            $original = $agency->getOriginal();
            $agency->update($data);
            if ($agency->wasChanged('subscription_status')) {
                AgencyStatusChanged::dispatch($agency, $original['subscription_status']);
            }
            return $agency->fresh();
        });
    }

    public function deleteAgency(Agency $agency): bool
    {
        return DB::transaction(function () use ($agency) {
            if ($agency->agencyEmployees()->exists() || $agency->employerAgencyContracts()->exists() || $agency->assignments()->exists()) {
                return false;
            }
            return $agency->delete();
        });
    }

    public function getAgenciesWithFilters(array $filters): LengthAwarePaginator
    {
        $query = Agency::with(['user', 'headOffice']);
        if (isset($filters['status'])) {
            $query->where('subscription_status', $filters['status']);
        }
        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('legal_name', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('billing_email', 'like', '%' . $filters['search'] . '%');
            });
        }
        if (isset($filters['country'])) {
            $query->where('country', $filters['country']);
        }
        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function getDashboardStats(Agency $agency): array
    {
        return [
            'total_employees' => $agency->agencyEmployees()->where('status', 'active')->count(),
            'active_assignments' => $agency->assignments()->where('status', 'active')->count(),
            'pending_timesheets' => $agency->timesheets()->where('status', 'pending')->count(),
            'active_shifts' => $agency->shifts()->whereIn('status', ['scheduled', 'in_progress'])->count(),
            'pending_invoices' => $agency->invoices()->where('status', 'pending')->count(),
            'total_revenue' => $agency->invoices()->where('status', 'paid')->sum('total_amount'),
        ];
    }

    public function approveTimesheet(Timesheet $timesheet): Timesheet
    {
        if ($timesheet->status !== 'pending') {
            throw new \Exception('Timesheet already processed');
        }
        return DB::transaction(function () use ($timesheet) {
            $timesheet->update([
                'status' => 'agency_approved',
                'agency_approved_by_id' => auth()->id(),
                'agency_approved_at' => now(),
            ]);
            event(new TimesheetApproved($timesheet, 'agency'));
            return $timesheet->fresh();
        });
    }

    public function createAgent(Agency $agency, array $data): User
    {
        return DB::transaction(function () use ($agency, $data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => bcrypt($data['password']),
                'role' => 'agent',
                'status' => 'active',
            ]);
            $agency->agents()->create([
                'user_id' => $user->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'permissions' => $data['permissions'] ?? [],
            ]);
            return $user;
        });
    }

    public function createAssignment(Agency $agency, array $data): Assignment
    {
        return DB::transaction(function () use ($agency, $data) {
            $agencyEmployee = AgencyEmployee::where('agency_id', $agency->id)
                ->where('employee_id', $data['employee_id'])
                ->where('status', 'active')
                ->firstOrFail();
            $contract = EmployerAgencyContract::where('agency_id', $agency->id)
                ->where('employer_id', $data['employer_id'])
                ->where('status', 'active')
                ->firstOrFail();
            return Assignment::create([
                'contract_id' => $contract->id,
                'agency_employee_id' => $agencyEmployee->id,
                'location_id' => $data['location_id'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'] ?? null,
                'agreed_rate' => $data['agreed_rate'],
                'pay_rate' => $data['pay_rate'] ?? $agencyEmployee->pay_rate,
                'assignment_type' => $data['assignment_type'] ?? 'standard',
                'status' => 'active',
                'created_by_id' => auth()->id(),
            ]);
        });
    }

    public function createAssignmentFromResponse(AgencyResponse $agencyResponse): Assignment
    {
        if ($agencyResponse->status !== 'accepted') {
            throw new \Exception('Only accepted agency responses can create assignments');
        }
        return DB::transaction(function () use ($agencyResponse) {
            $agencyEmployee = AgencyEmployee::where('agency_id', $agencyResponse->agency_id)
                ->where('employee_id', $agencyResponse->proposed_employee_id)
                ->where('status', 'active')
                ->firstOrFail();
            $contract = EmployerAgencyContract::where('agency_id', $agencyResponse->agency_id)
                ->where('employer_id', $agencyResponse->shiftRequest->employer_id)
                ->where('status', 'active')
                ->firstOrFail();
            if ($agencyResponse->proposed_rate > $agencyResponse->shiftRequest->max_hourly_rate) {
                throw new \Exception('Proposed rate exceeds shift request maximum');
            }
            $assignment = Assignment::create([
                'contract_id' => $contract->id,
                'agency_employee_id' => $agencyEmployee->id,
                'shift_request_id' => $agencyResponse->shift_request_id,
                'agency_response_id' => $agencyResponse->id,
                'location_id' => $agencyResponse->shiftRequest->location_id,
                'start_date' => $agencyResponse->proposed_start_date,
                'end_date' => $agencyResponse->proposed_end_date,
                'agreed_rate' => $agencyResponse->proposed_rate,
                'pay_rate' => $agencyEmployee->pay_rate,
                'assignment_type' => 'standard',
                'status' => 'active',
                'created_by_id' => auth()->id(),
            ]);
            $agencyResponse->update(['status' => 'assigned']);
            return $assignment;
        });
    }

    public function submitAgencyResponse(ShiftRequest $shiftRequest, Agency $agency, array $data): AgencyResponse
    {
        if (!$this->hasActiveContractWithEmployer($agency, $shiftRequest->employer_id)) {
            throw new \Exception('No active contract with this employer');
        }
        if ($data['proposed_rate'] > $shiftRequest->max_hourly_rate) {
            throw new \Exception('Proposed rate exceeds maximum allowed rate');
        }
        return DB::transaction(function () use ($shiftRequest, $agency, $data) {
            $existingResponse = AgencyResponse::where('shift_request_id', $shiftRequest->id)
                ->where('agency_id', $agency->id)
                ->whereIn('status', ['pending', 'accepted'])
                ->first();
            if ($existingResponse) {
                throw new \Exception('Active response already exists for this shift request');
            }
            if (isset($data['proposed_employee_id'])) {
                $agencyEmployee = AgencyEmployee::where('agency_id', $agency->id)
                    ->where('employee_id', $data['proposed_employee_id'])
                    ->where('status', 'active')
                    ->firstOrFail();
                if (!$this->isEmployeeAvailableForDates($data['proposed_employee_id'], $data['proposed_start_date'], $data['proposed_end_date'])) {
                    throw new \Exception('Employee not available for proposed dates');
                }
            }
            return AgencyResponse::create([
                'shift_request_id' => $shiftRequest->id,
                'agency_id' => $agency->id,
                'proposed_employee_id' => $data['proposed_employee_id'] ?? null,
                'proposed_rate' => $data['proposed_rate'],
                'proposed_start_date' => $data['proposed_start_date'],
                'proposed_end_date' => $data['proposed_end_date'] ?? null,
                'terms' => $data['terms'] ?? null,
                'estimated_total_hours' => $data['estimated_total_hours'] ?? null,
                'status' => 'pending',
                'submitted_by_id' => auth()->id(),
            ]);
        });
    }

    public function createShiftFromTemplate(ShiftTemplate $template, string $date): Shift
    {
        if ($template->status !== 'active') {
            throw new \Exception('Shift template is not active');
        }
        if ($template->effective_start_date && $date < $template->effective_start_date) {
            throw new \Exception('Date is before template effective start date');
        }
        if ($template->effective_end_date && $date > $template->effective_end_date) {
            throw new \Exception('Date is after template effective end date');
        }
        return DB::transaction(function () use ($template, $date) {
            $startTime = \Carbon\Carbon::parse($date . ' ' . $template->start_time);
            $endTime = \Carbon\Carbon::parse($date . ' ' . $template->end_time);
            if (!$this->isEmployeeAvailableForShift($template->assignment->agency_employee_id, $startTime, $endTime)) {
                throw new \Exception('Employee not available for this shift');
            }
            return Shift::create([
                'assignment_id' => $template->assignment_id,
                'location_id' => $template->assignment->location_id,
                'shift_date' => $date,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'hourly_rate' => $template->assignment->pay_rate,
                'status' => 'scheduled',
            ]);
        });
    }

    public function getAvailableAgencyEmployees(Agency $agency, array $filters = [])
    {
        $query = $agency->agencyEmployees()
            ->where('status', 'active')
            ->with(['employee.user', 'employee.employeeAvailability']);
        if (isset($filters['location_id'])) {
            $query->whereHas('preferred_locations', function ($q) use ($filters) {
                $q->where('location_id', $filters['location_id']);
            });
        }
        if (isset($filters['qualifications'])) {
            $query->whereHas('employee.qualifications', function ($q) use ($filters) {
                $q->whereIn('qualification', $filters['qualifications']);
            });
        }
        return $query->get()->filter(function ($agencyEmployee) use ($filters) {
            if (isset($filters['start_date']) && isset($filters['end_date'])) {
                return $this->isEmployeeAvailableForDates($agencyEmployee->employee_id, $filters['start_date'], $filters['end_date']);
            }
            return true;
        });
    }

    public function getAgencyEmployees(Agency $agency, array $filters = []): LengthAwarePaginator
    {
        $query = $agency->agencyEmployees()->with(['employee.user', 'branch']);
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }
        if (isset($filters['employment_type'])) {
            $query->where('employment_type', $filters['employment_type']);
        }
        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function getEmployerContracts(Agency $agency): LengthAwarePaginator
    {
        return $agency->employerAgencyContracts()
            ->with(['employer'])
            ->where('status', 'active')
            ->paginate(15);
    }

    public function getAssignments(Agency $agency, array $filters = []): LengthAwarePaginator
    {
        $query = $agency->assignments()->with(['agencyEmployee.employee.user', 'contract.employer', 'location']);
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function getTimesheets(Agency $agency, array $filters = []): LengthAwarePaginator
    {
        $query = $agency->timesheets()->with(['shift.assignment.agencyEmployee.employee.user']);
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['period_start'])) {
            $query->whereHas('shift', function ($q) use ($filters) {
                $q->where('start_time', '>=', $filters['period_start']);
            });
        }
        if (isset($filters['period_end'])) {
            $query->whereHas('shift', function ($q) use ($filters) {
                $q->where('end_time', '<=', $filters['period_end']);
            });
        }
        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function getShifts(Agency $agency, array $filters = []): LengthAwarePaginator
    {
        $query = $agency->shifts()->with(['assignment.contract.employer', 'location', 'assignment.agencyEmployee.employee.user']);
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['start_date'])) {
            $query->where('start_time', '>=', $filters['start_date']);
        }
        if (isset($filters['end_date'])) {
            $query->where('end_time', '<=', $filters['end_date']);
        }
        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function getInvoices(Agency $agency, array $filters = []): LengthAwarePaginator
    {
        $query = $agency->invoices()->with(['from', 'to']);
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function getPayroll(Agency $agency, array $filters = []): LengthAwarePaginator
    {
        $query = $agency->payroll()->with(['agencyEmployee.employee.user']);
        if (isset($filters['period_start'])) {
            $query->where('period_start', '>=', $filters['period_start']);
        }
        if (isset($filters['period_end'])) {
            $query->where('period_end', '<=', $filters['period_end']);
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function getPayouts(Agency $agency): LengthAwarePaginator
    {
        return $agency->payouts()->with(['payroll'])->paginate(15);
    }

    public function registerEmployeeWithAgency(Agency $agency, array $data): AgencyEmployee
    {
        return DB::transaction(function () use ($agency, $data) {
            $existingRegistration = AgencyEmployee::where('agency_id', $agency->id)
                ->where('employee_id', $data['employee_id'])
                ->where('status', 'active')
                ->first();
            if ($existingRegistration) {
                throw new \Exception('Employee is already actively registered with this agency');
            }
            return AgencyEmployee::create([
                'agency_id' => $agency->id,
                'employee_id' => $data['employee_id'],
                'branch_id' => $data['branch_id'] ?? null,
                'position' => $data['position'] ?? null,
                'pay_rate' => $data['pay_rate'],
                'employment_type' => $data['employment_type'] ?? 'temp',
                'status' => 'active',
                'contract_start_date' => $data['contract_start_date'] ?? null,
                'contract_end_date' => $data['contract_end_date'] ?? null,
                'specializations' => $data['specializations'] ?? null,
                'preferred_locations' => $data['preferred_locations'] ?? null,
                'max_weekly_hours' => $data['max_weekly_hours'] ?? null,
            ]);
        });
    }

    public function updateAgencyEmployee(AgencyEmployee $agencyEmployee, array $data): AgencyEmployee
    {
        if (isset($data['status']) && $data['status'] === 'terminated') {
            $activeAssignments = $agencyEmployee->assignments()->where('status', 'active')->count();
            if ($activeAssignments > 0) {
                throw new \Exception('Cannot terminate employee with active assignments');
            }
        }
        $agencyEmployee->update($data);
        return $agencyEmployee->fresh();
    }

    public function updateAssignment(Assignment $assignment, array $data): Assignment
    {
        if (isset($data['status']) && $data['status'] === 'completed') {
            $activeShifts = $assignment->shifts()->whereIn('status', ['scheduled', 'in_progress'])->count();
            if ($activeShifts > 0) {
                throw new \Exception('Cannot complete assignment with active shifts');
            }
        }
        $assignment->update($data);
        return $assignment->fresh();
    }

    public function offerShiftToEmployee(Shift $shift, AgencyEmployee $agencyEmployee): ShiftOffer
    {
        if ($shift->assignment->agency_employee_id !== $agencyEmployee->id) {
            throw new \Exception('Shift does not belong to this agency employee assignment');
        }
        if (!$this->isEmployeeAvailableForShift($agencyEmployee->employee_id, $shift->start_time, $shift->end_time)) {
            throw new \Exception('Employee not available for this shift');
        }
        return DB::transaction(function () use ($shift, $agencyEmployee) {
            $existingOffer = ShiftOffer::where('shift_id', $shift->id)
                ->where('agency_employee_id', $agencyEmployee->id)
                ->whereIn('status', ['pending', 'accepted'])
                ->first();
            if ($existingOffer) {
                throw new \Exception('Active shift offer already exists');
            }
            return ShiftOffer::create([
                'shift_id' => $shift->id,
                'agency_employee_id' => $agencyEmployee->id,
                'offered_by_id' => auth()->id(),
                'status' => 'pending',
                'expires_at' => now()->addHours(24),
            ]);
        });
    }

    public function processPayroll(Agency $agency, array $data): Payout
    {
        return DB::transaction(function () use ($agency, $data) {
            $timesheets = Timesheet::whereHas('shift.assignment', function ($q) use ($agency) {
                $q->where('agency_id', $agency->id);
            })
                ->where('status', 'employer_approved')
                ->whereBetween('created_at', [$data['period_start'], $data['period_end']])
                ->get();
            $payrollRecords = [];
            $totalAmount = 0;
            $employeeCount = 0;
            foreach ($timesheets->groupBy('shift.assignment.agency_employee_id') as $agencyEmployeeId => $employeeTimesheets) {
                $totalHours = $employeeTimesheets->sum('hours_worked');
                $agencyEmployee = AgencyEmployee::find($agencyEmployeeId);
                $grossPay = $totalHours * $agencyEmployee->pay_rate;
                $taxes = $grossPay * 0.20;
                $netPay = $grossPay - $taxes;
                $payroll = Payroll::create([
                    'agency_employee_id' => $agencyEmployeeId,
                    'period_start' => $data['period_start'],
                    'period_end' => $data['period_end'],
                    'total_hours' => $totalHours,
                    'gross_pay' => $grossPay,
                    'taxes' => $taxes,
                    'net_pay' => $netPay,
                    'status' => 'processing',
                ]);
                $payrollRecords[] = $payroll;
                $totalAmount += $netPay;
                $employeeCount++;
            }
            $payout = Payout::create([
                'agency_id' => $agency->id,
                'period_start' => $data['period_start'],
                'period_end' => $data['period_end'],
                'total_amount' => $totalAmount,
                'employee_count' => $employeeCount,
                'status' => 'processing',
            ]);
            foreach ($payrollRecords as $payroll) {
                $payroll->update(['payout_id' => $payout->id]);
            }
            return $payout;
        });
    }

    public function generateInvoiceForAssignment(Assignment $assignment, array $data): Invoice
    {
        return DB::transaction(function () use ($assignment, $data) {
            $totalHours = $assignment->shifts()
                ->where('status', 'completed')
                ->whereHas('timesheet', function ($q) {
                    $q->where('status', 'employer_approved');
                })
                ->whereBetween('shift_date', [$data['period_start'], $data['period_end']])
                ->get()
                ->sum(function ($shift) {
                    return $shift->timesheet->hours_worked;
                });
            $subtotal = $totalHours * $assignment->agreed_rate;
            $markup = $subtotal * ($assignment->agency->default_markup_percent / 100);
            $totalAmount = $subtotal + $markup;
            return Invoice::create([
                'type' => 'employer_to_agency',
                'from_type' => Employer::class,
                'from_id' => $assignment->contract->employer_id,
                'to_type' => Agency::class,
                'to_id' => $assignment->agency->id,
                'reference' => 'INV-' . strtoupper(uniqid()),
                'line_items' => [
                    [
                        'description' => 'Shift work for period ' . $data['period_start'] . ' to ' . $data['period_end'],
                        'quantity' => $totalHours,
                        'unit_price' => $assignment->agreed_rate,
                        'amount' => $subtotal,
                    ],
                    [
                        'description' => 'Agency markup (' . $assignment->agency->default_markup_percent . '%)',
                        'quantity' => 1,
                        'unit_price' => $markup,
                        'amount' => $markup,
                    ]
                ],
                'subtotal' => $subtotal,
                'tax_amount' => 0,
                'total_amount' => $totalAmount,
                'status' => 'pending',
                'due_date' => now()->addDays(14),
                'metadata' => [
                    'assignment_id' => $assignment->id,
                    'period_start' => $data['period_start'],
                    'period_end' => $data['period_end'],
                ],
            ]);
        });
    }

    public function syncAgencyWithEmployer(Agency $agency, int $employerId, array $contractData): bool
    {
        return DB::transaction(function () use ($agency, $employerId, $contractData) {
            $contract = $agency->employerAgencyContracts()->where('employer_id', $employerId)->first();
            if ($contract) {
                return $contract->update($contractData);
            }
            return (bool) $agency->employerAgencyContracts()->create(array_merge($contractData, ['employer_id' => $employerId]));
        });
    }

    public function getAgencyResponseStats(Agency $agency, array $filters = []): array
    {
        $query = $agency->agencyResponses();
        if (isset($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }
        if (isset($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }
        $total = $query->count();
        $accepted = $query->clone()->where('status', 'accepted')->count();
        $pending = $query->clone()->where('status', 'pending')->count();
        $rejected = $query->clone()->where('status', 'rejected')->count();
        return [
            'total_responses' => $total,
            'accepted' => $accepted,
            'pending' => $pending,
            'rejected' => $rejected,
            'acceptance_rate' => $total > 0 ? round(($accepted / $total) * 100, 2) : 0,
        ];
    }

    private function hasActiveContractWithEmployer(Agency $agency, int $employerId): bool
    {
        return $agency->employerAgencyContracts()->where('employer_id', $employerId)->where('status', 'active')->exists();
    }

    private function isEmployeeAvailableForDates(int $employeeId, string $startDate, ?string $endDate = null): bool
    {
        $conflictingAssignments = Assignment::whereHas('agencyEmployee', function ($q) use ($employeeId) {
            $q->where('employee_id', $employeeId);
        })
            ->where('status', 'active')
            ->where(function ($q) use ($startDate, $endDate) {
                $q->where('start_date', '<=', $endDate ?? $startDate)
                    ->where(function ($q) use ($startDate) {
                        $q->whereNull('end_date')->orWhere('end_date', '>=', $startDate);
                    });
            })
            ->exists();
        if ($conflictingAssignments) {
            return false;
        }
        $timeOffConflict = \App\Models\TimeOffRequest::where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->where('start_date', '<=', $endDate ?? $startDate)
            ->where('end_date', '>=', $startDate)
            ->exists();
        return !$timeOffConflict;
    }

    private function isEmployeeAvailableForShift(int $employeeId, $startTime, $endTime): bool
    {
        $conflictingShifts = Shift::whereHas('assignment.agencyEmployee', function ($q) use ($employeeId) {
            $q->where('employee_id', $employeeId);
        })
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime)
            ->exists();
        return !$conflictingShifts;
    }
}
