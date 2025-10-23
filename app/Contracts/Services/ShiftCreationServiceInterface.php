<?php

namespace App\Contracts\Services;

use App\Models\Shift;
use Illuminate\Database\Eloquent\Collection;

interface ShiftCreationServiceInterface
{
    public function createShift(array $data): Shift;
    public function createRecurringShift(array $data): Collection;
    public function updateShift(Shift $shift, array $data): Shift;
    public function cancelShift(Shift $shift, string $reason): void;
}
