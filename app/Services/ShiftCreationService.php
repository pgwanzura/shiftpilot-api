<?php

// app/Services/ShiftCreationService.php

namespace App\Services;

use App\Contracts\Services\ShiftCreationServiceInterface;
use App\Models\Shift;
use App\Models\ShiftTemplate;
use Illuminate\Database\Eloquent\Collection;
use App\Events\ShiftCreated;
use App\Events\ShiftCancelled;
use Carbon\Carbon;

class ShiftCreationService implements ShiftCreationServiceInterface
{
    public function __construct(
        private NotificationService $notificationService,
        private ValidationService $validationService
    ) {
    }

    public function createShift(array $data): Shift
    {
        $this->validationService->validateShiftCreation($data);

        $shift = Shift::create([
            'employer_id' => $data['employer_id'],
            'location_id' => $data['location_id'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'role_requirement' => $data['role_requirement'] ?? null,
            'hourly_rate' => $data['hourly_rate'] ?? null,
            'status' => 'open',
            'created_by_type' => 'employer',
            'created_by_id' => auth()->id(),
        ]);

        event(new ShiftCreated($shift));

        // Notify agencies connected to this employer
        $this->notificationService->notifyAgenciesOfNewShift($shift);

        return $shift;
    }

    public function createRecurringShift(array $data): Collection
    {
        $shifts = new Collection();
        $template = ShiftTemplate::create($data);

        $startDate = Carbon::parse($data['start_date']);
        $endDate = Carbon::parse($data['end_date'] ?? $startDate->copy()->addMonths(3));

        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            if ($this->isScheduledDay($currentDate, $template->day_of_week)) {
                $shift = $this->createShiftFromTemplate($template, $currentDate);
                $shifts->push($shift);
            }
            $currentDate->addDay();
        }

        return $shifts;
    }

    public function updateShift(Shift $shift, array $data): Shift
    {
        $this->validationService->validateShiftUpdate($shift, $data);

        $shift->update($data);

        if (isset($data['status']) && $data['status'] === 'cancelled') {
            event(new ShiftCancelled($shift, auth()->user()));
        }

        return $shift->fresh();
    }

    public function cancelShift(Shift $shift, string $reason): void
    {
        $shift->update([
            'status' => 'cancelled',
            'meta' => array_merge($shift->meta ?? [], ['cancellation_reason' => $reason])
        ]);

        event(new ShiftCancelled($shift, auth()->user()));
        $this->notificationService->notifyShiftCancellation($shift);
    }

    private function createShiftFromTemplate(ShiftTemplate $template, Carbon $date): Shift
    {
        return Shift::create([
            'employer_id' => $template->employer_id,
            'location_id' => $template->location_id,
            'start_time' => $date->copy()->setTimeFrom($template->start_time),
            'end_time' => $date->copy()->setTimeFrom($template->end_time),
            'role_requirement' => $template->role_requirement,
            'hourly_rate' => $template->hourly_rate,
            'status' => 'open',
            'created_by_type' => 'employer',
            'created_by_id' => $template->created_by_id,
            'shift_template_id' => $template->id,
        ]);
    }

    private function isScheduledDay(Carbon $date, string $dayOfWeek): bool
    {
        return $date->englishDayOfWeek === ucfirst($dayOfWeek);
    }
}
