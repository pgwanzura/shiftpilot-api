<?php

namespace App\Services;

use App\Models\RateCard;
use Illuminate\Database\Eloquent\Collection;

class RateCardService
{
    public function getAllRateCards(): Collection
    {
        return RateCard::all();
    }

    public function createRateCard(array $data): RateCard
    {
        return RateCard::create($data);
    }

    public function getRateCardById(string $id): ?RateCard
    {
        return RateCard::find($id);
    }

    public function updateRateCard(RateCard $rateCard, array $data): RateCard
    {
        $rateCard->update($data);
        return $rateCard;
    }

    public function deleteRateCard(RateCard $rateCard): ?bool
    {
        return $rateCard->delete();
    }
}
