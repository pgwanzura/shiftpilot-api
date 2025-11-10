<?php

namespace App\Http\Requests\Calendar;

use Illuminate\Foundation\Http\FormRequest;

class CalendarEventsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'view' => 'sometimes|in:month,week,day,schedule,placement',
            'filter' => 'sometimes|in:all,shifts,placements,interviews,time_off,meetings,training,availabilities',
            'entity_type' => 'sometimes|in:shift,placement,interview,meeting,training,time_off,availability',
            'entity_id' => 'sometimes|integer',
            'employer_id' => 'sometimes|exists:employers,id',
            'agency_id' => 'sometimes|exists:agencies,id',
            'employee_id' => 'sometimes|exists:employees,id',
            'location_id' => 'sometimes|exists:locations,id',
            'status' => 'sometimes|array',
            'status.*' => 'sometimes|in:open,offered,assigned,completed,agency_approved,employer_approved,billed,cancelled,pending,approved,rejected',
            'priority' => 'sometimes|array',
            'priority.*' => 'sometimes|in:low,medium,high,urgent',
            'requires_action' => 'sometimes|boolean',
            'action_required_by' => 'sometimes|in:agency_admin,agent,employer_admin,contact,employee',
        ];
    }

    public function validated($key = null, $default = null)
    {
        $validated = parent::validated();

        return array_merge([
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->addMonths(2)->endOfMonth()->toDateString(),
            'view' => 'month',
        ], $validated);
    }
}
