<?php

// app/Services/ValidationService.php

namespace App\Services;

use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class ValidationService
{
    public function validateShiftCreation(array $data): void
    {
        $validator = Validator::make($data, [
            'employer_id' => 'required|exists:employers,id',
            'location_id' => 'required|exists:locations,id',
            'start_time' => 'required|date|after:now',
            'end_time' => 'required|date|after:start_time',
            'role_requirement' => 'sometimes|string|max:255',
            'hourly_rate' => 'sometimes|numeric|min:0',
        ]);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        // Check for shift overlap
        $this->validateNoOverlappingShifts($data);
    }

    private function validateNoOverlappingShifts(array $data): void
    {
        $overlappingShift = Shift::where('location_id', $data['location_id'])
            ->where('status', '!=', 'cancelled')
            ->where(function ($query) use ($data) {
                $query->whereBetween('start_time', [$data['start_time'], $data['end_time']])
                      ->orWhereBetween('end_time', [$data['start_time'], $data['end_time']])
                      ->orWhere(function ($q) use ($data) {
                          $q->where('start_time', '<=', $data['start_time'])
                            ->where('end_time', '>=', $data['end_time']);
                      });
            })
            ->first();

        if ($overlappingShift) {
            throw new \Exception(__('shifts.validation.overlap'));
        }
    }
}
