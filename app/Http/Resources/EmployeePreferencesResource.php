<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EmployeePreferencesResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'preferred_shift_types' => $this->preferred_shift_types,
            'preferred_locations' => $this->preferred_locations,
            'preferred_industries' => $this->preferred_industries,
            'preferred_roles' => $this->preferred_roles,
            'max_travel_distance_km' => $this->max_travel_distance_km,
            'min_hourly_rate' => $this->min_hourly_rate,
            'preferred_shift_lengths' => $this->preferred_shift_lengths,
            'preferred_days' => $this->preferred_days,
            'preferred_start_times' => $this->preferred_start_times,
            'preferred_employment_types' => $this->preferred_employment_types,
            'notification_preferences' => $this->notification_preferences,
            'communication_preferences' => $this->communication_preferences,
            'auto_accept_offers' => $this->auto_accept_offers,
            'max_shifts_per_week' => $this->max_shifts_per_week,
            'has_preferences' => $this->has_preferences,
            'is_auto_accept_enabled' => $this->is_auto_accept_enabled,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
