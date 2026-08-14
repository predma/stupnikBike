<?php

namespace App\Services;

use App\Models\Bike;
use App\Models\BikePrice;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class BikePricingService
{
    public function activePrice(Bike $bike, CarbonImmutable $date, string $billingType = 'daily'): ?BikePrice
    {
        return BikePrice::query()
            ->where('bike_id', $bike->id)
            ->where('billing_type', $billingType)
            ->where('is_active', true)
            ->whereDate('effective_from', '<=', $date->toDateString())
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->first();
    }

    public function pricePayload(Bike $bike, CarbonImmutable $date, string $billingType = 'daily'): array
    {
        $price = $this->activePrice($bike, $date, $billingType);

        return [
            'id' => $price?->id,
            'price' => $price ? (float) $price->price : 0,
            'billing_type' => $billingType,
            'effective_from' => $price?->effective_from?->toDateString(),
            'has_price' => (bool) $price,
        ];
    }

    public function calculateTotal(Bike $bike, CarbonImmutable $date, int $quantity, string $billingType = 'daily'): float
    {
        $price = $this->activePrice($bike, $date, $billingType);

        if (! $price) {
            throw ValidationException::withMessages([
                'bike_id' => 'Za odabrani bicikl i datum nije definiran cjenik.',
            ]);
        }

        return round((float) $price->price * max(1, $quantity), 2);
    }
}
