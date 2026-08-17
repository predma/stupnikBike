<?php

namespace App\Services;

use App\Models\Bike;
use App\Models\Reservation;
use App\Models\ReservationSetting;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class ReservationAvailabilityService
{
    public function __construct(private readonly BikePricingService $pricing)
    {
    }

    public function availability(
        Bike $bike,
        CarbonImmutable $date,
        ?Reservation $ignoreReservation = null,
        ?CarbonImmutable $calendarFrom = null
    ): array
    {
        $setting = $this->settingFor($bike, $date);
        $slots = $setting->isHourly()
            ? collect($setting->normalizedSlots())->map(function (array $slot) use ($bike, $date, $ignoreReservation) {
                $start = CarbonImmutable::parse($date->toDateString().' '.$slot['start']);
                $end = CarbonImmutable::parse($date->toDateString().' '.$slot['end']);
                $availableUnits = $this->availableUnitsForRange($bike, $start, $end, $ignoreReservation);

                return [
                    'start' => $slot['start'],
                    'end' => $slot['end'],
                    'available' => $availableUnits > 0,
                    'available_units' => $availableUnits,
                ];
            })->values()->all()
            : [];

        return [
            'bike' => $bike->loadMissing('station'),
            'stock_quantity' => (int) $bike->stock_quantity,
            'selected_date' => $date->toDateString(),
            'max_quantity' => $setting->isDaily()
                ? $this->availableUnitsForDay($bike, $date, $ignoreReservation)
                : $this->maxAvailableFromSlots($slots),
            'pricing' => $this->pricing->pricePayload($bike, $date, 'daily'),
            'setting' => [
                'id' => $setting->id,
                'name' => $setting->name,
                'mode' => $setting->mode,
                'effective_from' => $setting->effective_from?->toDateString(),
                'max_days_per_reservation' => (int) ($setting->max_days_per_reservation ?: 1),
                'slots' => $setting->normalizedSlots(),
            ],
            'days' => $this->days($bike, $setting, $calendarFrom ?? $date, $ignoreReservation),
            'slots' => $slots,
        ];
    }

    public function validateOrFail(
        Bike $bike,
        CarbonImmutable $startsAt,
        CarbonImmutable $endsAt,
        int $quantity,
        ?Reservation $ignoreReservation = null
    ): ReservationSetting {
        $setting = $this->settingFor($bike, $startsAt);

        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'quantity' => 'Količina mora biti barem 1.',
            ]);
        }

        if ($startsAt->startOfDay()->lessThan(now()->toImmutable()->startOfDay())) {
            throw ValidationException::withMessages([
                'starts_at' => 'Nije moguće rezervirati prošli datum.',
            ]);
        }

        if ($setting->isHourly()) {
            if (! $startsAt->isSameDay($endsAt)) {
                throw ValidationException::withMessages([
                    'starts_at' => 'Satna rezervacija mora biti unutar istog dana.',
                ]);
            }

            $matchesSlot = collect($setting->normalizedSlots())->contains(function (array $slot) use ($startsAt, $endsAt): bool {
                return $startsAt->format('H:i') === $slot['start'] && $endsAt->format('H:i') === $slot['end'];
            });

            if (! $matchesSlot) {
                throw ValidationException::withMessages([
                    'starts_at' => 'Odabrani termin nije dopušten za ovaj bicikl.',
                ]);
            }
        }

        if ($setting->isDaily()) {
            $requestedDays = (int) $startsAt->startOfDay()->diffInDays($endsAt->startOfDay()) + 1;
            $maxDays = max(1, (int) ($setting->max_days_per_reservation ?: 1));

            if ($requestedDays > $maxDays) {
                throw ValidationException::withMessages([
                    'ends_at' => "Jedna rezervacija može trajati najviše {$maxDays} dana.",
                ]);
            }
        }

        $availableUnits = $setting->isDaily()
            ? $this->availableUnitsForDailyRange($bike, $startsAt, $endsAt, $ignoreReservation)
            : $this->availableUnitsForRange($bike, $startsAt, $endsAt, $ignoreReservation);

        if ($quantity > $availableUnits) {
            throw ValidationException::withMessages([
                'quantity' => "Za odabrani datum/termin slobodno je još {$availableUnits} bicikala.",
            ]);
        }

        return $setting;
    }

    public function settingFor(Bike $bike, CarbonImmutable $date): ReservationSetting
    {
        return ReservationSetting::activeForBike($bike, $date) ?? new ReservationSetting([
            'name' => 'Default daily',
            'mode' => 'daily',
            'effective_from' => now()->toDateString(),
            'max_days_per_reservation' => 1,
            'slots' => [],
            'is_active' => true,
        ]);
    }

    private function days(Bike $bike, ReservationSetting $setting, CarbonImmutable $fromDate, ?Reservation $ignoreReservation): array
    {
        $start = $fromDate->startOfDay();
        $today = now()->toImmutable()->startOfDay();
        $effectiveFrom = $setting->effective_from ? CarbonImmutable::parse($setting->effective_from)->startOfDay() : null;

        if ($today->greaterThan($start)) {
            $start = $today;
        }

        if ($effectiveFrom && $effectiveFrom->greaterThan($start)) {
            $start = $effectiveFrom;
        }

        $daysToReturn = max(30, (int) ($setting->max_days_per_reservation ?: 1));

        return collect(range(0, $daysToReturn - 1))->map(function (int $offset) use ($bike, $setting, $start, $ignoreReservation) {
            $day = $start->addDays($offset);
            $availableUnits = $setting->isDaily()
                ? $this->availableUnitsForDay($bike, $day, $ignoreReservation)
                : $this->availableUnitsForSlots($bike, $setting, $day, $ignoreReservation);

            return [
                'date' => $day->toDateString(),
                'available' => $availableUnits > 0,
                'available_units' => $availableUnits,
            ];
        })->all();
    }

    private function availableUnitsForDay(Bike $bike, CarbonImmutable $day, ?Reservation $ignoreReservation = null): int
    {
        $reserved = (int) $this->baseReservationQuery($bike, $ignoreReservation)
            ->where(fn ($query) => $query->whereBetween('starts_at', [$day->startOfDay(), $day->endOfDay()])
                ->orWhereBetween('ends_at', [$day->startOfDay(), $day->endOfDay()])
                ->orWhere(fn ($q) => $q->where('starts_at', '<=', $day->startOfDay())->where('ends_at', '>=', $day->endOfDay())))
            ->sum('quantity');

        return max(0, (int) $bike->stock_quantity - $reserved);
    }

    private function availableUnitsForDailyRange(Bike $bike, CarbonImmutable $startsAt, CarbonImmutable $endsAt, ?Reservation $ignoreReservation = null): int
    {
        $start = $startsAt->startOfDay();
        $end = $endsAt->startOfDay();

        if ($end->lessThan($start)) {
            return 0;
        }

        $days = (int) $start->diffInDays($end) + 1;

        return collect(range(0, $days - 1))
            ->map(fn (int $offset) => $this->availableUnitsForDay($bike, $start->addDays($offset), $ignoreReservation))
            ->min() ?? 0;
    }

    private function availableUnitsForSlots(Bike $bike, ReservationSetting $setting, CarbonImmutable $day, ?Reservation $ignoreReservation): int
    {
        $slots = $setting->normalizedSlots();

        if (empty($slots)) {
            return $this->availableUnitsForDay($bike, $day, $ignoreReservation);
        }

        return $this->maxAvailableFromSlots(collect($slots)->map(function (array $slot) use ($bike, $day, $ignoreReservation) {
            $start = CarbonImmutable::parse($day->toDateString().' '.$slot['start']);
            $end = CarbonImmutable::parse($day->toDateString().' '.$slot['end']);

            return [
                'available_units' => $this->availableUnitsForRange($bike, $start, $end, $ignoreReservation),
            ];
        })->all());
    }

    private function availableUnitsForRange(Bike $bike, CarbonImmutable $startsAt, CarbonImmutable $endsAt, ?Reservation $ignoreReservation = null): int
    {
        $reserved = (int) $this->baseReservationQuery($bike, $ignoreReservation)
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->sum('quantity');

        return max(0, (int) $bike->stock_quantity - $reserved);
    }

    private function maxAvailableFromSlots(array $slots): int
    {
        return max(0, (int) (collect($slots)->max('available_units') ?? 0));
    }

    private function baseReservationQuery(Bike $bike, ?Reservation $ignoreReservation)
    {
        return Reservation::query()
            ->where('bike_id', $bike->id)
            ->where('status', '!=', 'cancelled')
            ->when($ignoreReservation, fn ($query) => $query->whereKeyNot($ignoreReservation->getKey()));
    }
}
