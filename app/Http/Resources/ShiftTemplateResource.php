<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ShiftTemplateResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'employer_id' => $this->employer_id,
            'location_id' => $this->location_id,
            'title' => $this->title,
            'description' => $this->description,
            'day_of_week' => $this->day_of_week,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'role_requirement' => $this->role_requirement,
            'required_qualifications' => $this->required_qualifications,
            'hourly_rate' => $this->hourly_rate,
            'recurrence_type' => $this->recurrence_type,
            'status' => $this->status,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'created_by_type' => $this->created_by_type,
            'created_by_id' => $this->created_by_id,
            'meta' => $this->meta,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'employer' => $this->whenLoaded('employer'),
            'location' => $this->whenLoaded('location'),
            'shifts' => $this->whenLoaded('shifts'),
        ];
    }
}
