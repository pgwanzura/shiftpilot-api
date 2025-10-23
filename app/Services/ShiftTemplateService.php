<?php

namespace App\Services;

use App\Models\ShiftTemplate;
use App\Models\Shift;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ShiftTemplateService
{
    public function getShiftTemplates(array $filters = []): LengthAwarePaginator
    {
        $query = ShiftTemplate::with(['employer', 'location']);

        if (isset($filters['employer_id'])) {
            $query->where('employer_id', $filters['employer_id']);
        }

        if (isset($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['day_of_week'])) {
            $query->where('day_of_week', $filters['day_of_week']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('role_requirement', 'like', "%{$search}%")
                  ->orWhereHas('location', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    public function createShiftTemplate(array $data): ShiftTemplate
    {
        return DB::transaction(function () use ($data) {
            $user = auth()->user();
            $createdByType = $user->role === 'employer_admin' ? 'employer' : 'agency';

            return ShiftTemplate::create([
                'employer_id' => $data['employer_id'],
                'location_id' => $data['location_id'],
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'day_of_week' => $data['day_of_week'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'role_requirement' => $data['role_requirement'] ?? null,
                'required_qualifications' => $data['required_qualifications'] ?? null,
                'hourly_rate' => $data['hourly_rate'] ?? null,
                'recurrence_type' => $data['recurrence_type'],
                'status' => 'active',
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'created_by_type' => $createdByType,
                'created_by_id' => $user->id,
            ]);
        });
    }

    public function updateShiftTemplate(ShiftTemplate $template, array $data): ShiftTemplate
    {
        $template->update($data);
        return $template->fresh();
    }

    public function deleteShiftTemplate(ShiftTemplate $template): void
    {
        DB::transaction(function () use ($template) {
            if ($template->shifts()->exists()) {
                throw new \Exception('Cannot delete template with associated shifts');
            }
            $template->delete();
        });
    }

    public function generateShiftsFromTemplate(ShiftTemplate $template, array $data): array
    {
        return DB::transaction(function () use ($template, $data) {
            $startDate = Carbon::parse($data['start_date']);
            $endDate = Carbon::parse($data['end_date']);
            $generatedShifts = [];

            $currentDate = $startDate->copy();
            while ($currentDate <= $endDate) {
                if ($this->shouldGenerateShift($template, $currentDate)) {
                    $shift = $this->createShiftFromTemplate($template, $currentDate);
                    $generatedShifts[] = $shift;
                }
                $currentDate->addDay();
            }

            return $generatedShifts;
        });
    }

    private function shouldGenerateShift(ShiftTemplate $template, Carbon $date): bool
    {
        if (!$this->isCorrectDayOfWeek($template->day_of_week, $date)) {
            return false;
        }

        if ($template->start_date && $date->lt(Carbon::parse($template->start_date))) {
            return false;
        }

        if ($template->end_date && $date->gt(Carbon::parse($template->end_date))) {
            return false;
        }

        if ($template->recurrence_type === 'biweekly' && $date->week % 2 !== 0) {
            return false;
        }

        if ($template->recurrence_type === 'monthly' && $date->day > 7) {
            return false;
        }

        return true;
    }

    private function isCorrectDayOfWeek(string $templateDay, Carbon $date): bool
    {
        $dayMap = [
            'mon' => Carbon::MONDAY,
            'tue' => Carbon::TUESDAY,
            'wed' => Carbon::WEDNESDAY,
            'thu' => Carbon::THURSDAY,
            'fri' => Carbon::FRIDAY,
            'sat' => Carbon::SATURDAY,
            'sun' => Carbon::SUNDAY,
        ];

        return $date->dayOfWeek === $dayMap[$templateDay];
    }

    private function createShiftFromTemplate(ShiftTemplate $template, Carbon $date): Shift
    {
        $startTime = Carbon::parse($date->format('Y-m-d') . ' ' . $template->start_time);
        $endTime = Carbon::parse($date->format('Y-m-d') . ' ' . $template->end_time);

        return Shift::create([
            'employer_id' => $template->employer_id,
            'location_id' => $template->location_id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'hourly_rate' => $template->hourly_rate,
            'role_requirement' => $template->role_requirement,
            'status' => 'open',
            'created_by_type' => 'template',
            'created_by_id' => $template->id,
            'meta' => [
                'generated_from_template' => $template->id,
                'template_title' => $template->title,
            ]
        ]);
    }

    public function deactivateTemplate(ShiftTemplate $template): ShiftTemplate
    {
        $template->update(['status' => 'inactive']);
        return $template->fresh();
    }

    public function activateTemplate(ShiftTemplate $template): ShiftTemplate
    {
        $template->update(['status' => 'active']);
        return $template->fresh();
    }
}
