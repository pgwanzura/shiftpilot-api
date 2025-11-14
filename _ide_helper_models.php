<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string|null $legal_name
 * @property string|null $registration_number
 * @property string|null $billing_email
 * @property string|null $phone
 * @property string|null $address_line1
 * @property string|null $address_line2
 * @property string|null $city
 * @property string|null $county
 * @property string|null $country
 * @property string|null $postcode
 * @property string|null $latitude
 * @property string|null $longitude
 * @property numeric $default_markup_percent
 * @property \App\Enums\SubscriptionStatus $subscription_status
 * @property array<array-key, mixed>|null $meta
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AgencyBranch> $AgencyBranches
 * @property-read int|null $agency_branches_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AgencyEmployee> $agencyEmployees
 * @property-read int|null $agency_employees_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AgencyResponse> $agencyResponses
 * @property-read int|null $agency_responses_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Agent> $agents
 * @property-read int|null $agents_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Assignment> $assignments
 * @property-read int|null $assignments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\EmployerAgencyContract> $employerAgencyContracts
 * @property-read int|null $employer_agency_contracts_count
 * @property-read \App\Models\AgencyBranch|null $headOffice
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Invoice> $invoices
 * @property-read int|null $invoices_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Payroll> $payrolls
 * @property-read int|null $payrolls_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ShiftRequest> $shiftRequests
 * @property-read int|null $shift_requests_count
 * @property-read \App\Models\Subscription|null $subscription
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TimeOffRequest> $timeOffRequests
 * @property-read int|null $time_off_requests_count
 * @property-read \App\Models\User|null $user
 * @method static \Database\Factories\AgencyFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agency newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agency newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agency query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agency whereAddressLine1($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agency whereAddressLine2($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agency whereBillingEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agency whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agency whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agency whereCounty($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agency whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agency whereDefaultMarkupPercent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agency whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agency whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agency whereLegalName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agency whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agency whereMeta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agency whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agency wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agency wherePostcode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agency whereRegistrationNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agency whereSubscriptionStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agency whereUpdatedAt($value)
 */
	class Agency extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $agency_id
 * @property string $name
 * @property string|null $branch_code
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $address_line1
 * @property string|null $address_line2
 * @property string|null $city
 * @property string|null $county
 * @property string|null $postcode
 * @property string $country
 * @property numeric|null $latitude
 * @property numeric|null $longitude
 * @property string|null $contact_name
 * @property string|null $contact_email
 * @property string|null $contact_phone
 * @property int $is_head_office
 * @property \App\Enums\AgencyBranchStatus $status
 * @property array<array-key, mixed>|null $opening_hours
 * @property array<array-key, mixed>|null $services_offered
 * @property array<array-key, mixed>|null $meta
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Agency $agency
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AgencyEmployee> $agencyEmployees
 * @property-read int|null $agency_employees_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Agent> $agents
 * @property-read int|null $agents_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Assignment> $assignments
 * @property-read int|null $assignments_count
 * @property-read string $full_address
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Payroll> $payrolls
 * @property-read int|null $payrolls_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyBranch active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyBranch newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyBranch newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyBranch query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyBranch whereAddressLine1($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyBranch whereAddressLine2($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyBranch whereAgencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyBranch whereBranchCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyBranch whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyBranch whereContactEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyBranch whereContactName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyBranch whereContactPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyBranch whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyBranch whereCounty($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyBranch whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyBranch whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyBranch whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyBranch whereIsHeadOffice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyBranch whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyBranch whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyBranch whereMeta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyBranch whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyBranch whereOpeningHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyBranch wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyBranch wherePostcode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyBranch whereServicesOffered($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyBranch whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyBranch whereUpdatedAt($value)
 */
	class AgencyBranch extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $agency_id
 * @property int $employee_id
 * @property string|null $position
 * @property numeric $pay_rate
 * @property string $employment_type
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $contract_start_date
 * @property \Illuminate\Support\Carbon|null $contract_end_date
 * @property array<array-key, mixed>|null $specializations
 * @property array<array-key, mixed>|null $preferred_locations
 * @property int|null $max_weekly_hours
 * @property string|null $notes
 * @property array<array-key, mixed>|null $meta
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Agency $agency
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Assignment> $assignments
 * @property-read int|null $assignments_count
 * @property-read \App\Models\Employee $employee
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Payroll> $payrolls
 * @property-read int|null $payrolls_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ShiftOffer> $shiftOffers
 * @property-read int|null $shift_offers_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyEmployee active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyEmployee newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyEmployee newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyEmployee query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyEmployee whereAgencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyEmployee whereContractEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyEmployee whereContractStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyEmployee whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyEmployee whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyEmployee whereEmploymentType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyEmployee whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyEmployee whereMaxWeeklyHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyEmployee whereMeta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyEmployee whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyEmployee wherePayRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyEmployee wherePosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyEmployee wherePreferredLocations($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyEmployee whereSpecializations($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyEmployee whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyEmployee whereUpdatedAt($value)
 */
	class AgencyEmployee extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $shift_request_id
 * @property int $agency_id
 * @property int|null $proposed_employee_id
 * @property numeric $proposed_rate
 * @property \Illuminate\Support\Carbon $proposed_start_date
 * @property \Illuminate\Support\Carbon|null $proposed_end_date
 * @property string|null $terms
 * @property int|null $estimated_total_hours
 * @property string $status
 * @property string|null $notes
 * @property int $submitted_by_id
 * @property int|null $employer_decision_by_id
 * @property \Illuminate\Support\Carbon|null $responded_at
 * @property \Illuminate\Support\Carbon|null $employer_decision_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Agency $agency
 * @property-read \App\Models\Assignment|null $assignment
 * @property-read \App\Models\User|null $employerDecisionBy
 * @property-read \App\Models\Employee|null $proposedEmployee
 * @property-read \App\Models\ShiftRequest $shiftRequest
 * @property-read \App\Models\User $submittedBy
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyResponse accepted()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyResponse forAgency($agencyId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyResponse forShiftRequest($shiftRequestId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyResponse newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyResponse newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyResponse pending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyResponse query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyResponse rejected()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyResponse whereAgencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyResponse whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyResponse whereEmployerDecisionAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyResponse whereEmployerDecisionById($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyResponse whereEstimatedTotalHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyResponse whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyResponse whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyResponse whereProposedEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyResponse whereProposedEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyResponse whereProposedRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyResponse whereProposedStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyResponse whereRespondedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyResponse whereShiftRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyResponse whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyResponse whereSubmittedById($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyResponse whereTerms($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyResponse whereUpdatedAt($value)
 */
	class AgencyResponse extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property int $agency_id
 * @property array<array-key, mixed>|null $permissions
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Agency $agency
 * @method static \Database\Factories\AgentFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agent query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agent whereAgencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agent whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agent whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agent wherePermissions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agent whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agent whereUserId($value)
 */
	class Agent extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $contract_id
 * @property int $agency_employee_id
 * @property int|null $shift_request_id
 * @property int|null $agency_response_id
 * @property int $location_id
 * @property string $role
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon|null $end_date
 * @property int|null $expected_hours_per_week
 * @property numeric $agreed_rate
 * @property numeric $pay_rate
 * @property numeric $markup_amount
 * @property numeric $markup_percent
 * @property \App\Enums\AssignmentStatus $status
 * @property \App\Enums\AssignmentType $assignment_type
 * @property array<array-key, mixed>|null $shift_pattern
 * @property string|null $notes
 * @property int $created_by_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Agency|null $agency
 * @property-read \App\Models\AgencyEmployee $agencyEmployee
 * @property-read \App\Models\AgencyResponse|null $agencyResponse
 * @property-read \App\Models\EmployerAgencyContract $contract
 * @property-read \App\Models\User $createdBy
 * @property-read mixed $duration_days
 * @property-read \App\Models\Employee|null $employee
 * @property-read \App\Models\Employer|null $employer
 * @property-read mixed $is_direct_assignment_appended
 * @property-read mixed $is_ongoing
 * @property-read mixed $is_standard_assignment_appended
 * @property-read \App\Models\Location $location
 * @property-read \App\Models\ShiftRequest|null $shiftRequest
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ShiftTemplate> $shiftTemplates
 * @property-read int|null $shift_templates_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Shift> $shifts
 * @property-read int|null $shifts_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Timesheet> $timesheets
 * @property-read int|null $timesheets_count
 * @property-read mixed $total_expected_hours
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment cancelled()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment completed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment current()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment dateRange($startDate, $endDate = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment direct()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment forAgency($agencyId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment forEmployee($employeeId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment forEmployer($employerId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment overdue()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment pending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment standard()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment suspended()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment whereAgencyEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment whereAgencyResponseId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment whereAgreedRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment whereAssignmentType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment whereContractId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment whereCreatedById($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment whereExpectedHoursPerWeek($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment whereLocationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment whereMarkupAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment whereMarkupPercent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment wherePayRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment whereShiftPattern($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment whereShiftRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment withShifts()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment withoutShifts()
 */
	class Assignment extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $actor_type
 * @property int|null $actor_id
 * @property string $action
 * @property string|null $target_type
 * @property int|null $target_id
 * @property array<array-key, mixed>|null $payload
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent|null $actor
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent|null $target
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereActorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereActorType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog wherePayload($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereTargetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereTargetType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereUserAgent($value)
 */
	class AuditLog extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employer_id
 * @property int|null $user_id
 * @property string $role
 * @property int $can_approve_timesheets
 * @property int $can_approve_assignments
 * @property array<array-key, mixed>|null $meta
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Employer $employer
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ShiftApproval> $shiftApprovals
 * @property-read int|null $shift_approvals_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Timesheet> $timesheets
 * @property-read int|null $timesheets_count
 * @method static \Database\Factories\ContactFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereCanApproveAssignments($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereCanApproveTimesheets($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereEmployerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereMeta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereUserId($value)
 */
	class Contact extends \Eloquent {}
}

namespace App\Models{
/**
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Country newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Country newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Country query()
 */
	class Country extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property string|null $national_insurance_number
 * @property \Illuminate\Support\Carbon|null $date_of_birth
 * @property string|null $address_line1
 * @property string|null $address_line2
 * @property string|null $city
 * @property string|null $county
 * @property string|null $postcode
 * @property string|null $country
 * @property numeric|null $latitude
 * @property numeric|null $longitude
 * @property string|null $emergency_contact_name
 * @property string|null $emergency_contact_phone
 * @property array<array-key, mixed>|null $qualifications
 * @property array<array-key, mixed>|null $certifications
 * @property string $status
 * @property array<array-key, mixed>|null $meta
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Agency> $agencies
 * @property-read int|null $agencies_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AgencyEmployee> $agencyEmployees
 * @property-read int|null $agency_employees_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Assignment> $assignments
 * @property-read int|null $assignments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\EmployeeAvailability> $employeeAvailabilities
 * @property-read int|null $employee_availabilities_count
 * @property-read int|null $age
 * @property-read string $full_address
 * @property-read bool $has_active_agencies
 * @property-read bool $is_active
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Payroll> $payrolls
 * @property-read int|null $payrolls_count
 * @property-read \App\Models\EmployeePreference|null $preferences
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ShiftOffer> $shiftOffers
 * @property-read int|null $shift_offers_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Shift> $shifts
 * @property-read int|null $shifts_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TimeOffRequest> $timeOffRequests
 * @property-read int|null $time_off_requests_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Timesheet> $timesheets
 * @property-read int|null $timesheets_count
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee availableForShift($startTime, $endTime)
 * @method static \Database\Factories\EmployeeFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee inactive()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee suspended()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereAddressLine1($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereAddressLine2($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereCertifications($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereCounty($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereDateOfBirth($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereEmergencyContactName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereEmergencyContactPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereMeta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereNationalInsuranceNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee wherePostcode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereQualifications($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee withActiveAgencies()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee withQualifications(array $qualifications)
 */
	class Employee extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employee_id
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon|null $end_date
 * @property int $days_mask
 * @property string $start_time
 * @property string $end_time
 * @property string $type
 * @property int $priority
 * @property int|null $max_hours
 * @property bool $flexible
 * @property array<array-key, mixed>|null $constraints
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Employee $employee
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeAvailability available()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeAvailability currentlyEffective()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeAvailability forDate(\Carbon\Carbon $date)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeAvailability forShift(\Carbon\Carbon $start, \Carbon\Carbon $end)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeAvailability newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeAvailability newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeAvailability preferred()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeAvailability query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeAvailability whereConstraints($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeAvailability whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeAvailability whereDaysMask($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeAvailability whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeAvailability whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeAvailability whereEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeAvailability whereFlexible($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeAvailability whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeAvailability whereMaxHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeAvailability wherePriority($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeAvailability whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeAvailability whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeAvailability whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeAvailability whereUpdatedAt($value)
 */
	class EmployeeAvailability extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employee_id
 * @property array<array-key, mixed>|null $preferred_shift_types
 * @property array<array-key, mixed>|null $preferred_locations
 * @property array<array-key, mixed>|null $preferred_industries
 * @property array<array-key, mixed>|null $preferred_roles
 * @property int|null $max_travel_distance_km
 * @property numeric|null $min_hourly_rate
 * @property array<array-key, mixed>|null $preferred_shift_lengths
 * @property array<array-key, mixed>|null $preferred_days
 * @property array<array-key, mixed>|null $preferred_start_times
 * @property array<array-key, mixed>|null $preferred_employment_types
 * @property array<array-key, mixed>|null $notification_preferences
 * @property array<array-key, mixed>|null $communication_preferences
 * @property bool $auto_accept_offers
 * @property int|null $max_shifts_per_week
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Employee $employee
 * @property-read bool $has_preferences
 * @property-read bool $is_auto_accept_enabled
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeePreference newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeePreference newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeePreference query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeePreference whereAutoAcceptOffers($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeePreference whereCommunicationPreferences($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeePreference whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeePreference whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeePreference whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeePreference whereMaxShiftsPerWeek($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeePreference whereMaxTravelDistanceKm($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeePreference whereMinHourlyRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeePreference whereNotificationPreferences($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeePreference wherePreferredDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeePreference wherePreferredEmploymentTypes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeePreference wherePreferredIndustries($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeePreference wherePreferredLocations($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeePreference wherePreferredRoles($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeePreference wherePreferredShiftLengths($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeePreference wherePreferredShiftTypes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeePreference wherePreferredStartTimes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeePreference whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeePreference withAutoAccept()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeePreference withLocationPreference($locationId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeePreference withMinRate($minRate)
 */
	class EmployeePreference extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string|null $legal_name
 * @property string|null $registration_number
 * @property string|null $billing_email
 * @property string|null $phone
 * @property string|null $website
 * @property string|null $address_line1
 * @property string|null $address_line2
 * @property string|null $city
 * @property string|null $county
 * @property string|null $postcode
 * @property string|null $country
 * @property string|null $industry
 * @property string|null $company_size
 * @property string $status
 * @property array<array-key, mixed>|null $meta
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Assignment> $assignments
 * @property-read int|null $assignments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Contact> $contacts
 * @property-read int|null $contacts_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\EmployerAgencyContract> $employerAgencyContracts
 * @property-read int|null $employer_agency_contracts_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Location> $locations
 * @property-read int|null $locations_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\RateCard> $rateCards
 * @property-read int|null $rate_cards_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Subscription> $subscriptions
 * @property-read int|null $subscriptions_count
 * @method static \Database\Factories\EmployerFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employer query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employer whereAddressLine1($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employer whereAddressLine2($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employer whereBillingEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employer whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employer whereCompanySize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employer whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employer whereCounty($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employer whereIndustry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employer whereLegalName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employer whereMeta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employer whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employer wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employer wherePostcode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employer whereRegistrationNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employer whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employer whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employer whereWebsite($value)
 */
	class Employer extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employer_id
 * @property int $agency_id
 * @property string $status
 * @property string|null $contract_document_url
 * @property \Illuminate\Support\Carbon|null $contract_start
 * @property \Illuminate\Support\Carbon|null $contract_end
 * @property string|null $terms
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Agency $agency
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Assignment> $assignments
 * @property-read int|null $assignments_count
 * @property-read \App\Models\Employer $employer
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployerAgencyContract newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployerAgencyContract newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployerAgencyContract query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployerAgencyContract whereAgencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployerAgencyContract whereContractDocumentUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployerAgencyContract whereContractEnd($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployerAgencyContract whereContractStart($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployerAgencyContract whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployerAgencyContract whereEmployerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployerAgencyContract whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployerAgencyContract whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployerAgencyContract whereTerms($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployerAgencyContract whereUpdatedAt($value)
 */
	class EmployerAgencyContract extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employer_id
 * @property int $agency_id
 * @property string $status
 * @property string|null $contract_document_url
 * @property string|null $contract_start
 * @property string|null $contract_end
 * @property string|null $terms
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Agency $agency
 * @property-read \App\Models\Employer $employer
 * @method static \Database\Factories\EmployerAgencyLinkFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployerAgencyLink newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployerAgencyLink newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployerAgencyLink query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployerAgencyLink whereAgencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployerAgencyLink whereContractDocumentUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployerAgencyLink whereContractEnd($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployerAgencyLink whereContractStart($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployerAgencyLink whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployerAgencyLink whereEmployerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployerAgencyLink whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployerAgencyLink whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployerAgencyLink whereTerms($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployerAgencyLink whereUpdatedAt($value)
 */
	class EmployerAgencyLink extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $type
 * @property string $from_type
 * @property int $from_id
 * @property string $to_type
 * @property int $to_id
 * @property string|null $reference
 * @property array<array-key, mixed>|null $line_items
 * @property numeric $subtotal
 * @property numeric $tax_amount
 * @property numeric $total_amount
 * @property string $status
 * @property \Illuminate\Support\Carbon $due_date
 * @property \Illuminate\Support\Carbon|null $paid_at
 * @property string|null $payment_reference
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $from
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Payment> $payments
 * @property-read int|null $payments_count
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $to
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereDueDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereFromId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereFromType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereLineItems($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice wherePaidAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice wherePaymentReference($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereReference($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereSubtotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereTaxAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereToId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereToType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereUpdatedAt($value)
 */
	class Invoice extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employer_id
 * @property string $name
 * @property string|null $address_line1
 * @property string|null $address_line2
 * @property string|null $city
 * @property string|null $county
 * @property string|null $postcode
 * @property string|null $country
 * @property numeric|null $latitude
 * @property numeric|null $longitude
 * @property string|null $location_type
 * @property string|null $contact_name
 * @property string|null $contact_phone
 * @property string|null $instructions
 * @property array<array-key, mixed>|null $meta
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Assignment> $assignments
 * @property-read int|null $assignments_count
 * @property-read \App\Models\Employer $employer
 * @property-read mixed $full_address
 * @property-read mixed $has_complete_address
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\RateCard> $rateCards
 * @property-read int|null $rate_cards_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ShiftRequest> $shiftRequests
 * @property-read int|null $shift_requests_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Shift> $shifts
 * @property-read int|null $shifts_count
 * @method static \Database\Factories\LocationFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Location newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Location newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Location query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Location whereAddressLine1($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Location whereAddressLine2($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Location whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Location whereContactName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Location whereContactPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Location whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Location whereCounty($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Location whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Location whereEmployerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Location whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Location whereInstructions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Location whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Location whereLocationType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Location whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Location whereMeta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Location whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Location wherePostcode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Location whereUpdatedAt($value)
 */
	class Location extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $invoice_id
 * @property string $payer_type
 * @property int $payer_id
 * @property numeric $amount
 * @property string $method
 * @property string|null $processor_id
 * @property string $status
 * @property numeric $fee_amount
 * @property numeric $net_amount
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Invoice $invoice
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $payer
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereFeeAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereInvoiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereNetAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment wherePayerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment wherePayerType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereProcessorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereUpdatedAt($value)
 */
	class Payment extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $invoice_id
 * @property numeric $amount_paid
 * @property string $currency
 * @property string $payment_method
 * @property \Illuminate\Support\Carbon $payment_date
 * @property string|null $reference
 * @property string|null $notes
 * @property string $status
 * @property int $logged_by_id
 * @property int|null $confirmed_by_id
 * @property \Illuminate\Support\Carbon|null $confirmed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $confirmedBy
 * @property-read \App\Models\Invoice $invoice
 * @property-read \App\Models\User $loggedBy
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentLog confirmed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentLog pending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentLog whereAmountPaid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentLog whereConfirmedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentLog whereConfirmedById($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentLog whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentLog whereInvoiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentLog whereLoggedById($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentLog whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentLog wherePaymentDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentLog wherePaymentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentLog whereReference($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentLog whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentLog whereUpdatedAt($value)
 */
	class PaymentLog extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $agency_id
 * @property \Illuminate\Support\Carbon $period_start
 * @property \Illuminate\Support\Carbon $period_end
 * @property numeric $total_amount
 * @property string $status
 * @property string|null $provider_payout_id
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Agency $agency
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Payroll> $payrolls
 * @property-read int|null $payrolls_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payout newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payout newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payout query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payout whereAgencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payout whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payout whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payout whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payout wherePeriodEnd($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payout wherePeriodStart($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payout whereProviderPayoutId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payout whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payout whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payout whereUpdatedAt($value)
 */
	class Payout extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $agency_id
 * @property int $employee_id
 * @property \Illuminate\Support\Carbon $period_start
 * @property \Illuminate\Support\Carbon $period_end
 * @property numeric $total_hours
 * @property numeric $gross_pay
 * @property numeric $taxes
 * @property numeric $net_pay
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $paid_at
 * @property int|null $payout_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\AgencyEmployee|null $agencyEmployee
 * @property-read \App\Models\Payout|null $payout
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereAgencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereGrossPay($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereNetPay($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll wherePaidAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll wherePayoutId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll wherePeriodEnd($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll wherePeriodStart($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereTaxes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereTotalHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereUpdatedAt($value)
 */
	class Payroll extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property numeric $commission_rate
 * @property numeric $transaction_fee_flat
 * @property numeric $transaction_fee_percent
 * @property int $payout_schedule_days
 * @property numeric $tax_vat_rate_percent
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformBilling newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformBilling newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformBilling query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformBilling whereCommissionRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformBilling whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformBilling whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformBilling wherePayoutScheduleDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformBilling whereTaxVatRatePercent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformBilling whereTransactionFeeFlat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformBilling whereTransactionFeePercent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformBilling whereUpdatedAt($value)
 */
	class PlatformBilling extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $employer_id
 * @property int|null $agency_id
 * @property string $role_key
 * @property int|null $location_id
 * @property string|null $day_of_week
 * @property \Illuminate\Support\Carbon|null $start_time
 * @property \Illuminate\Support\Carbon|null $end_time
 * @property numeric $rate
 * @property string $currency
 * @property \Illuminate\Support\Carbon|null $effective_from
 * @property \Illuminate\Support\Carbon|null $effective_to
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Agency|null $agency
 * @property-read \App\Models\Employer|null $employer
 * @property-read \App\Models\Location|null $location
 * @method static \Database\Factories\RateCardFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RateCard newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RateCard newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RateCard query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RateCard whereAgencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RateCard whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RateCard whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RateCard whereDayOfWeek($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RateCard whereEffectiveFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RateCard whereEffectiveTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RateCard whereEmployerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RateCard whereEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RateCard whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RateCard whereLocationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RateCard whereRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RateCard whereRoleKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RateCard whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RateCard whereUpdatedAt($value)
 */
	class RateCard extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $assignment_id
 * @property \Illuminate\Support\Carbon $shift_date
 * @property \Illuminate\Support\Carbon $start_time
 * @property \Illuminate\Support\Carbon $end_time
 * @property numeric $hourly_rate
 * @property \App\Enums\ShiftStatus $status
 * @property string|null $notes
 * @property array<array-key, mixed>|null $meta
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Agency|null $agency
 * @property-read \App\Models\Assignment $assignment
 * @property-read mixed $duration_hours
 * @property-read \App\Models\Employee|null $employee
 * @property-read \App\Models\Employer|null $employer
 * @property-read mixed $is_future
 * @property-read mixed $is_ongoing
 * @property-read mixed $is_past
 * @property-read \App\Models\Location|null $location
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ShiftApproval> $shiftApprovals
 * @property-read int|null $shift_approvals_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ShiftOffer> $shiftOffers
 * @property-read int|null $shift_offers_count
 * @property-read \App\Models\Timesheet|null $timesheet
 * @property-read mixed $total_earnings
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift cancelled()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift completed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift current()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift dateRange($startDate, $endDate = null)
 * @method static \Database\Factories\ShiftFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift forAgency($agencyId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift forAssignment($assignmentId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift forEmployee($employeeId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift forEmployer($employerId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift inProgress()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift noShow()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift overlapping($startTime, $endTime, $excludeId = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift past()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift scheduled()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift upcoming()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereAssignmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereHourlyRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereMeta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereShiftDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereUpdatedAt($value)
 */
	class Shift extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $shift_id
 * @property int $contact_id
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $signed_at
 * @property string|null $signature_blob_url
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Contact $contact
 * @property-read \App\Models\Shift $shift
 * @method static \Database\Factories\ShiftApprovalFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftApproval newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftApproval newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftApproval query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftApproval whereContactId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftApproval whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftApproval whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftApproval whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftApproval whereShiftId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftApproval whereSignatureBlobUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftApproval whereSignedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftApproval whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftApproval whereUpdatedAt($value)
 */
	class ShiftApproval extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $shift_id
 * @property int $employee_id
 * @property int $offered_by_id
 * @property \App\Enums\ShiftOfferStatus $status
 * @property \Illuminate\Support\Carbon $expires_at
 * @property \Illuminate\Support\Carbon|null $responded_at
 * @property string|null $response_notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\AgencyEmployee|null $agencyEmployee
 * @property-read \App\Models\User $offeredBy
 * @property-read \App\Models\Shift $shift
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftOffer accepted()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftOffer active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftOffer expired()
 * @method static \Database\Factories\ShiftOfferFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftOffer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftOffer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftOffer pending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftOffer query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftOffer rejected()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftOffer requiringAction()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftOffer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftOffer whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftOffer whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftOffer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftOffer whereOfferedById($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftOffer whereRespondedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftOffer whereResponseNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftOffer whereShiftId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftOffer whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftOffer whereUpdatedAt($value)
 */
	class ShiftOffer extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employer_id
 * @property int $location_id
 * @property string $title
 * @property string|null $description
 * @property string $role
 * @property array<array-key, mixed>|null $required_qualifications
 * @property string $experience_level
 * @property int $background_check_required
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon|null $end_date
 * @property string $shift_pattern
 * @property array<array-key, mixed>|null $recurrence_rules
 * @property numeric $max_hourly_rate
 * @property string $currency
 * @property int $number_of_workers
 * @property string $target_agencies
 * @property array<array-key, mixed>|null $specific_agency_ids
 * @property \Illuminate\Support\Carbon|null $response_deadline
 * @property string $status
 * @property int $created_by_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AgencyResponse> $agencyResponses
 * @property-read int|null $agency_responses_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Assignment> $assignments
 * @property-read int|null $assignments_count
 * @property-read \App\Models\User $createdBy
 * @property-read \App\Models\Employer $employer
 * @property-read \App\Models\Location $location
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftRequest active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftRequest forAgency($agencyId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftRequest published()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftRequest whereBackgroundCheckRequired($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftRequest whereCreatedById($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftRequest whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftRequest whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftRequest whereEmployerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftRequest whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftRequest whereExperienceLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftRequest whereLocationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftRequest whereMaxHourlyRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftRequest whereNumberOfWorkers($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftRequest whereRecurrenceRules($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftRequest whereRequiredQualifications($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftRequest whereResponseDeadline($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftRequest whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftRequest whereShiftPattern($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftRequest whereSpecificAgencyIds($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftRequest whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftRequest whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftRequest whereTargetAgencies($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftRequest whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftRequest whereUpdatedAt($value)
 */
	class ShiftRequest extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $assignment_id
 * @property string $name
 * @property \App\Enums\DayOfWeek $day_of_week
 * @property \Illuminate\Support\Carbon $start_time
 * @property \Illuminate\Support\Carbon $end_time
 * @property int $break_minutes
 * @property int $required_employees
 * @property string|null $recurrence_pattern
 * @property \Illuminate\Support\Carbon|null $effective_start_date
 * @property \Illuminate\Support\Carbon|null $effective_end_date
 * @property \Illuminate\Support\Carbon|null $last_generated_date
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \App\Enums\RecurrenceType $recurrence_type
 * @property \App\Enums\ShiftTemplateStatus $status
 * @property-read \App\Models\Assignment $assignment
 * @property-read string $duration
 * @property-read mixed $employer
 * @property-read mixed $location
 * @property-read mixed $role
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Shift> $shifts
 * @property-read int|null $shifts_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftTemplate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftTemplate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftTemplate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftTemplate whereAssignmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftTemplate whereBreakMinutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftTemplate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftTemplate whereDayOfWeek($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftTemplate whereEffectiveEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftTemplate whereEffectiveStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftTemplate whereEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftTemplate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftTemplate whereLastGeneratedDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftTemplate whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftTemplate whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftTemplate whereRecurrencePattern($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftTemplate whereRequiredEmployees($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftTemplate whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftTemplate whereUpdatedAt($value)
 */
	class ShiftTemplate extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $entity_type
 * @property int $entity_id
 * @property string $plan_key
 * @property string $plan_name
 * @property numeric $amount
 * @property string $interval
 * @property string $status
 * @property \Illuminate\Support\Carbon $started_at
 * @property \Illuminate\Support\Carbon|null $current_period_start
 * @property \Illuminate\Support\Carbon|null $current_period_end
 * @property array<array-key, mixed>|null $meta
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $subscriber
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereCurrentPeriodEnd($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereCurrentPeriodStart($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereEntityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereEntityType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereInterval($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereMeta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription wherePlanKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription wherePlanName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereUpdatedAt($value)
 */
	class Subscription extends \Eloquent {}
}

namespace App\Models{
/**
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $recipient
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemNotification newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemNotification newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemNotification query()
 */
	class SystemNotification extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employee_id
 * @property int $agency_id
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon $end_date
 * @property \App\Enums\TimeOffType $type
 * @property string|null $reason
 * @property \App\Enums\TimeOffRequestStatus $status
 * @property int|null $approved_by_id
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Agency $agency
 * @property-read \App\Models\User|null $approvedBy
 * @property-read \App\Models\Employee $employee
 * @method static \Database\Factories\TimeOffRequestFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeOffRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeOffRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeOffRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeOffRequest whereAgencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeOffRequest whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeOffRequest whereApprovedById($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeOffRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeOffRequest whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeOffRequest whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeOffRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeOffRequest whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeOffRequest whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeOffRequest whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeOffRequest whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeOffRequest whereUpdatedAt($value)
 */
	class TimeOffRequest extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $shift_id
 * @property int $employee_id
 * @property \Illuminate\Support\Carbon|null $clock_in
 * @property \Illuminate\Support\Carbon|null $clock_out
 * @property int $break_minutes
 * @property numeric|null $hours_worked
 * @property string $status
 * @property int|null $agency_approved_by
 * @property \Illuminate\Support\Carbon|null $agency_approved_at
 * @property int|null $approved_by_contact_id
 * @property string|null $approved_at
 * @property string|null $notes
 * @property array<array-key, mixed>|null $attachments
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $agencyApprovedBy
 * @property-read \App\Models\Employee $employee
 * @property-read \App\Models\Contact|null $employerApprovedBy
 * @property-read \App\Models\Shift $shift
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timesheet newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timesheet newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timesheet query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timesheet whereAgencyApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timesheet whereAgencyApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timesheet whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timesheet whereApprovedByContactId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timesheet whereAttachments($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timesheet whereBreakMinutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timesheet whereClockIn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timesheet whereClockOut($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timesheet whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timesheet whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timesheet whereHoursWorked($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timesheet whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timesheet whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timesheet whereShiftId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timesheet whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timesheet whereUpdatedAt($value)
 */
	class Timesheet extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string $role
 * @property string|null $phone
 * @property string $status
 * @property array<array-key, mixed>|null $meta
 * @property \Illuminate\Support\Carbon|null $last_login_at
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $display_role
 * @property-read bool $has_complete_profile
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User agencyUsers()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User byRole($role)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User employerUsers()
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLastLoginAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereMeta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 */
	class User extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $owner_type
 * @property int $owner_id
 * @property string $url
 * @property array<array-key, mixed> $events
 * @property string $secret
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $last_delivery_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $owner
 * @method static \Database\Factories\WebhookSubscriptionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookSubscription newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookSubscription newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookSubscription query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookSubscription whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookSubscription whereEvents($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookSubscription whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookSubscription whereLastDeliveryAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookSubscription whereOwnerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookSubscription whereOwnerType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookSubscription whereSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookSubscription whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookSubscription whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookSubscription whereUrl($value)
 */
	class WebhookSubscription extends \Eloquent {}
}

