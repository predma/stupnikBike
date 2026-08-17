<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bike;
use App\Models\Notification;
use App\Models\Reservation;
use App\Services\BikePricingService;
use App\Services\ReservationAvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReservationController extends Controller
{
    public function __construct(
        private readonly ReservationAvailabilityService $availability,
        private readonly BikePricingService $pricing
    )
    {
    }

    public function index()
    {
        return response()->json([
            'data' => Reservation::with(['bike', 'station', 'user', 'days'])->latest()->get(),
        ]);
    }

    public function show(Reservation $reservation)
    {
        return response()->json([
            'data' => $reservation->load(['bike', 'station', 'user', 'days']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'bike_id' => ['required', 'exists:bikes,id'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'station_id' => ['nullable', 'exists:stations,id'],
            'reservation_days' => ['nullable', 'array', 'min:1'],
            'reservation_days.*' => ['date'],
            'starts_at' => ['required_without:reservation_days', 'date'],
            'ends_at' => ['required_without:reservation_days', 'date', 'after:starts_at'],
            'payment_method' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $bike = Bike::query()->with('station')->findOrFail($data['bike_id']);
        $quantity = (int) ($data['quantity'] ?? 1);
        $stationId = $data['station_id'] ?? $bike->station_id;
        $selectedDays = $this->selectedDays($data);
        [$startsAt, $endsAt] = $this->dateBounds($data, $selectedDays);

        if ($selectedDays) {
            $this->availability->validateDaysOrFail($bike, $selectedDays, $quantity);
            $totalPrice = $this->pricing->calculateTotalForDays($bike, $selectedDays, $quantity, 'daily');
        } else {
            $this->availability->validateOrFail($bike, $startsAt, $endsAt, $quantity);
            $totalPrice = $this->pricing->calculateTotalForRange($bike, $startsAt, $endsAt, $quantity, 'daily');
        }

        $reservation = Reservation::create([
            'reservation_number' => sprintf('SB-%s', Str::upper(Str::random(8))),
            'user_id' => $request->user()->id,
            'bike_id' => $bike->id,
            'quantity' => $quantity,
            'station_id' => $stationId,
            'status' => 'confirmed',
            'payment_status' => 'unpaid',
            'payment_method' => $data['payment_method'] ?? null,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'total_price' => $totalPrice,
            'notes' => $data['notes'] ?? null,
        ]);
        $this->syncReservationDays($reservation, $selectedDays ?: $this->datesFromRange($startsAt, $endsAt));

        Notification::create([
            'user_id' => $request->user()->id,
            'type' => 'reservation',
            'title' => 'Rezervacija potvrđena',
            'body' => sprintf(
                'Tvoja rezervacija #%s za bicikl %s je uspješno kreirana.',
                $reservation->reservation_number,
                $bike->name
            ),
            'channel' => 'app',
            'is_read' => false,
        ]);

        return response()->json([
            'data' => $reservation->load(['bike', 'station', 'user', 'days']),
        ], 201);
    }

    public function update(Request $request, Reservation $reservation)
    {
        abort_unless($reservation->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
            'reservation_days' => ['nullable', 'array', 'min:1'],
            'reservation_days.*' => ['date'],
            'starts_at' => ['required_without:reservation_days', 'date'],
            'ends_at' => ['required_without:reservation_days', 'date', 'after:starts_at'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $bike = $reservation->bike()->firstOrFail();
        $quantity = (int) $data['quantity'];
        $selectedDays = $this->selectedDays($data);
        [$startsAt, $endsAt] = $this->dateBounds($data, $selectedDays);

        if ($selectedDays) {
            $this->availability->validateDaysOrFail($bike, $selectedDays, $quantity, $reservation);
            $totalPrice = $this->pricing->calculateTotalForDays($bike, $selectedDays, $quantity, 'daily');
        } else {
            $this->availability->validateOrFail($bike, $startsAt, $endsAt, $quantity, $reservation);
            $totalPrice = $this->pricing->calculateTotalForRange($bike, $startsAt, $endsAt, $quantity, 'daily');
        }

        $reservation->update([
            'quantity' => $quantity,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'total_price' => $totalPrice,
            'notes' => $data['notes'] ?? $reservation->notes,
        ]);
        $this->syncReservationDays($reservation, $selectedDays ?: $this->datesFromRange($startsAt, $endsAt));

        return response()->json([
            'data' => $reservation->load(['bike', 'station', 'user', 'days']),
        ]);
    }

    public function cancel(Request $request, Reservation $reservation)
    {
        abort_unless($reservation->user_id === $request->user()->id, 403);

        $reservation->update([
            'status' => 'cancelled',
        ]);

        Notification::create([
            'user_id' => $request->user()->id,
            'type' => 'reservation',
            'title' => 'Rezervacija otkazana',
            'body' => sprintf('Rezervacija #%s je otkazana.', $reservation->reservation_number),
            'channel' => 'app',
            'is_read' => false,
        ]);

        return response()->json([
            'data' => $reservation->load(['bike', 'station', 'user', 'days']),
        ]);
    }

    private function selectedDays(array $data): array
    {
        if (empty($data['reservation_days'])) {
            return [];
        }

        return $this->availability->normalizeSelectedDates($data['reservation_days']);
    }

    private function dateBounds(array $data, array $selectedDays): array
    {
        if ($selectedDays) {
            return [
                CarbonImmutable::parse($selectedDays[0])->startOfDay(),
                CarbonImmutable::parse($selectedDays[array_key_last($selectedDays)])->setTime(23, 59),
            ];
        }

        return [
            CarbonImmutable::parse($data['starts_at']),
            CarbonImmutable::parse($data['ends_at']),
        ];
    }

    private function datesFromRange(CarbonImmutable $startsAt, CarbonImmutable $endsAt): array
    {
        $start = $startsAt->startOfDay();
        $days = max(1, (int) $start->diffInDays($endsAt->startOfDay()) + 1);

        return collect(range(0, $days - 1))
            ->map(fn (int $offset) => $start->addDays($offset)->toDateString())
            ->all();
    }

    private function syncReservationDays(Reservation $reservation, array $dates): void
    {
        $reservation->days()->delete();

        foreach ($dates as $date) {
            $reservation->days()->create([
                'reservation_date' => $date,
            ]);
        }
    }
}
