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
            'data' => Reservation::with(['bike', 'station', 'user'])->latest()->get(),
        ]);
    }

    public function show(Reservation $reservation)
    {
        return response()->json([
            'data' => $reservation->load(['bike', 'station', 'user']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'bike_id' => ['required', 'exists:bikes,id'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'station_id' => ['nullable', 'exists:stations,id'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'payment_method' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $bike = Bike::query()->with('station')->findOrFail($data['bike_id']);
        $quantity = (int) ($data['quantity'] ?? 1);
        $startsAt = CarbonImmutable::parse($data['starts_at']);
        $endsAt = CarbonImmutable::parse($data['ends_at']);
        $stationId = $data['station_id'] ?? $bike->station_id;
        $this->availability->validateOrFail($bike, $startsAt, $endsAt, $quantity);
        $totalPrice = $this->pricing->calculateTotal($bike, $startsAt, $quantity, 'daily');

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
            'data' => $reservation->load(['bike', 'station', 'user']),
        ], 201);
    }

    public function update(Request $request, Reservation $reservation)
    {
        abort_unless($reservation->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $bike = $reservation->bike()->firstOrFail();
        $startsAt = CarbonImmutable::parse($data['starts_at']);
        $endsAt = CarbonImmutable::parse($data['ends_at']);
        $quantity = (int) $data['quantity'];

        $this->availability->validateOrFail($bike, $startsAt, $endsAt, $quantity, $reservation);

        $reservation->update([
            'quantity' => $quantity,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'total_price' => $this->pricing->calculateTotal($bike, $startsAt, $quantity, 'daily'),
            'notes' => $data['notes'] ?? $reservation->notes,
        ]);

        return response()->json([
            'data' => $reservation->load(['bike', 'station', 'user']),
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
            'data' => $reservation->load(['bike', 'station', 'user']),
        ]);
    }
}
