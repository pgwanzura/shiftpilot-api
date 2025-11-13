<?php

namespace App\Http\Requests\EmployeePreference;

use Illuminate\Foundation\Http\FormRequest;

class CreateEmployeePreferencesRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()->can('update', $this->route('employee'));
    }

    public function rules()
    {
        $employeeId = $this->route('employee')?->id;

        return [
            'preferred_shift_types' => 'nullable|array',
            'preferred_shift_types.*' => 'string|max:50',
            'preferred_locations' => 'nullable|array',
            'preferred_locations.*' => 'string|max:100',
            'preferred_industries' => 'nullable|array',
            'preferred_industries.*' => 'string|max:50',
            'preferred_roles' => 'nullable|array',
            'preferred_roles.*' => 'string|max:50',
            'max_travel_distance_km' => 'nullable|integer|min:1|max:500',
            'min_hourly_rate' => 'nullable|numeric|min:0',
            'preferred_shift_lengths' => 'nullable|array',
            'preferred_shift_lengths.*' => 'integer|min:1|max:24',
            'preferred_days' => 'nullable|array',
            'preferred_days.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'preferred_start_times' => 'nullable|array',
            'preferred_start_times.*' => 'date_format:H:i',
            'preferred_employment_types' => 'nullable|array',
            'preferred_employment_types.*' => 'in:temp,contract,temp_to_perm,permanent',
            'notification_preferences' => 'nullable|array',
            'communication_preferences' => 'nullable|array',
            'auto_accept_offers' => 'boolean',
            'max_shifts_per_week' => 'nullable|integer|min:1|max:7',
        ];
    }

    public function messages()
    {
        return [
            'preferred_days.*.in' => 'Preferred days must be valid day names',
            'preferred_start_times.*.date_format' => 'Start times must be in HH:MM format',
            'preferred_employment_types.*.in' => 'Employment types must be valid types',
            'max_travel_distance_km.min' => 'Travel distance must be at least 1km',
            'max_travel_distance_km.max' => 'Travel distance cannot exceed 500km',
            'min_hourly_rate.min' => 'Hourly rate cannot be negative',
        ];
    }

    public function prepareForValidation()
    {
        if ($this->has('preferred_shift_types') && is_string($this->preferred_shift_types)) {
            $this->merge([
                'preferred_shift_types' => json_decode($this->preferred_shift_types, true),
            ]);
        }

        if ($this->has('preferred_locations') && is_string($this->preferred_locations)) {
            $this->merge([
                'preferred_locations' => json_decode($this->preferred_locations, true),
            ]);
        }

        if ($this->has('preferred_industries') && is_string($this->preferred_industries)) {
            $this->merge([
                'preferred_industries' => json_decode($this->preferred_industries, true),
            ]);
        }

        if ($this->has('preferred_roles') && is_string($this->preferred_roles)) {
            $this->merge([
                'preferred_roles' => json_decode($this->preferred_roles, true),
            ]);
        }

        if ($this->has('preferred_shift_lengths') && is_string($this->preferred_shift_lengths)) {
            $this->merge([
                'preferred_shift_lengths' => json_decode($this->preferred_shift_lengths, true),
            ]);
        }

        if ($this->has('preferred_days') && is_string($this->preferred_days)) {
            $this->merge([
                'preferred_days' => json_decode($this->preferred_days, true),
            ]);
        }

        if ($this->has('preferred_start_times') && is_string($this->preferred_start_times)) {
            $this->merge([
                'preferred_start_times' => json_decode($this->preferred_start_times, true),
            ]);
        }

        if ($this->has('preferred_employment_types') && is_string($this->preferred_employment_types)) {
            $this->merge([
                'preferred_employment_types' => json_decode($this->preferred_employment_types, true),
            ]);
        }

        if ($this->has('notification_preferences') && is_string($this->notification_preferences)) {
            $this->merge([
                'notification_preferences' => json_decode($this->notification_preferences, true),
            ]);
        }

        if ($this->has('communication_preferences') && is_string($this->communication_preferences)) {
            $this->merge([
                'communication_preferences' => json_decode($this->communication_preferences, true),
            ]);
        }

        if ($this->has('auto_accept_offers') && is_string($this->auto_accept_offers)) {
            $this->merge([
                'auto_accept_offers' => $this->auto_accept_offers === 'true' || $this->auto_accept_offers === '1',
            ]);
        }
    }
}
