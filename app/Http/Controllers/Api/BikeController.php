<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bike;
use App\Models\Reservation;
use App\Services\BikePricingService;
use App\Services\ReservationAvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class BikeController extends Controller
{
    public function __construct(
        private readonly ReservationAvailabilityService $availability,
        private readonly BikePricingService $pricing,
    )
    {
    }

    public function index()
    {
        return response()->json([
            'data' => Bike::with('station')
                ->latest()
                ->get()
                ->map(fn (Bike $bike) => $this->formatBike($bike)),
        ]);
    }

    public function show(Bike $bike)
    {
        return response()->json([
            'data' => $this->formatBike($bike->load(['station', 'reservations.user', 'issues'])),
        ]);
    }

    public function availability(Request $request, Bike $bike)
    {
        $date = $request->filled('date')
            ? CarbonImmutable::parse((string) $request->string('date'))
            : now()->toImmutable();
        $reservation = $request->filled('reservation_id')
            ? Reservation::query()->where('user_id', $request->user()->id)->findOrFail($request->integer('reservation_id'))
            : null;

        return response()->json([
            'data' => $this->availability->availability($bike, $date, $reservation),
        ]);
    }

    private function formatBike(Bike $bike): array
    {
        return [
            ...$bike->toArray(),
            'current_pricing' => $this->pricing->pricePayload($bike, now()->toImmutable(), 'daily'),
        ];
    }
}
