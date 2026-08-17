<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bike;
use App\Models\Reservation;
use App\Models\Station;
use App\Models\User;
use App\Services\BikePricingService;
use App\Services\ReservationAvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
        $reservations = Reservation::with(['user', 'bike', 'station', 'days'])->latest()->get();

        return view('admin.resources.index', [
            'title' => 'Rezervacije',
            'subtitle' => 'CRUD za rezervacije i njihov status.',
            'headers' => ['Broj', 'Korisnik', 'Bicikl', 'Količina', 'Dani', 'Stanica', 'Status', 'Plaćanje', 'Način', 'Početak', 'Kraj'],
            'createUrl' => route('admin.reservations.create'),
            'rows' => $reservations->map(fn (Reservation $reservation) => [
                'cells' => [
                    $reservation->reservation_number,
                    $reservation->user?->name ?? '-',
                    $reservation->bike?->name ?? '-',
                    $reservation->quantity,
                    $reservation->days->pluck('reservation_date')->map(fn ($date) => $date?->format('d.m.Y.'))->implode(', ') ?: '-',
                    $reservation->station?->name ?? '-',
                    $reservation->status,
                    $reservation->payment_status ?? 'unpaid',
                    $reservation->payment_method ?? '-',
                    $reservation->starts_at?->format('d.m.Y. H:i'),
                    $reservation->ends_at?->format('d.m.Y. H:i'),
                ],
                'editUrl' => route('admin.reservations.edit', $reservation),
                'deleteUrl' => route('admin.reservations.destroy', $reservation),
            ]),
        ]);
    }

    public function create()
    {
        return view('admin.reservations.form', [
            'title' => 'Nova rezervacija',
            'subtitle' => 'Ručno kreiraj rezervaciju za korisnika.',
            'action' => route('admin.reservations.store'),
            'method' => 'POST',
            'backUrl' => route('admin.reservations.index'),
            'users' => User::orderBy('name')->get(),
            'bikes' => Bike::orderBy('name')->orderBy('size')->get(),
            'stations' => Station::orderBy('name')->get(),
            'reservation' => null,
            'values' => [
                'quantity' => 1,
                'status' => 'confirmed',
                'payment_status' => 'unpaid',
                'reservation_date' => now()->toDateString(),
                'reservation_end_date' => now()->toDateString(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request);
        $data['reservation_number'] = $data['reservation_number'] ?: sprintf('SB-%s', Str::upper(Str::random(8)));
        $data['quantity'] = (int) ($data['quantity'] ?? 1);
        $data['payment_status'] = $data['payment_status'] ?? 'unpaid';
        $data['payment_method'] = $data['payment_status'] === 'paid' ? ($data['payment_method'] ?? null) : null;
        $data['paid_at'] = $data['payment_status'] === 'paid' ? now() : null;
        $data['total_price'] = $this->calculateTotal($data);
        $reservation = Reservation::create($data);
        $this->syncReservationDaysFromRange($reservation);

        return redirect()->route('admin.reservations.index')->with('status', 'Rezervacija je kreirana.');
    }

    public function edit(Reservation $reservation)
    {
        return view('admin.reservations.form', [
            'title' => 'Uredi rezervaciju',
            'subtitle' => 'Promijeni trajanje, status ili korisnika.',
            'action' => route('admin.reservations.update', $reservation),
            'method' => 'PUT',
            'backUrl' => route('admin.reservations.index'),
            'users' => User::orderBy('name')->get(),
            'bikes' => Bike::orderBy('name')->orderBy('size')->get(),
            'stations' => Station::orderBy('name')->get(),
            'reservation' => $reservation,
            'values' => $this->formatValues($reservation),
        ]);
    }

    public function update(Request $request, Reservation $reservation)
    {
        $data = $this->validateRequest($request, $reservation);
        $data['quantity'] = (int) ($data['quantity'] ?? 1);
        $data['payment_status'] = $data['payment_status'] ?? 'unpaid';
        $data['payment_method'] = $data['payment_status'] === 'paid' ? ($data['payment_method'] ?? null) : null;
        $data['paid_at'] = $data['payment_status'] === 'paid' ? ($reservation->paid_at ?? now()) : null;
        $data['total_price'] = $this->calculateTotal($data);
        $reservation->update($data);
        $this->syncReservationDaysFromRange($reservation);

        return redirect()->route('admin.reservations.index')->with('status', 'Rezervacija je ažurirana.');
    }

    public function destroy(Reservation $reservation)
    {
        $reservation->delete();

        return redirect()->route('admin.reservations.index')->with('status', 'Rezervacija je obrisana.');
    }

    public function availability(Request $request)
    {
        $data = $request->validate([
            'bike_id' => ['required', 'exists:bikes,id'],
            'date' => ['required', 'date'],
            'calendar_from' => ['nullable', 'date'],
            'reservation_id' => ['nullable', 'exists:reservations,id'],
        ]);

        $bike = Bike::findOrFail($data['bike_id']);
        $reservation = ! empty($data['reservation_id']) ? Reservation::find($data['reservation_id']) : null;

        return response()->json([
            'data' => $this->availability->availability(
                $bike,
                CarbonImmutable::parse($data['date']),
                $reservation,
                ! empty($data['calendar_from']) ? CarbonImmutable::parse($data['calendar_from']) : null
            ),
        ]);
    }

    private function validateRequest(Request $request, Reservation|int|null $ignore = null): array
    {
        $ignoreId = $ignore instanceof Reservation ? $ignore->id : $ignore;
        $data = $request->validate([
            'reservation_number' => ['nullable', 'string', 'max:255', Rule::unique('reservations', 'reservation_number')->ignore($ignoreId)],
            'user_id' => ['required', 'exists:users,id'],
            'bike_id' => ['required', 'exists:bikes,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'station_id' => ['nullable', 'exists:stations,id'],
            'status' => ['required', 'string', 'max:255'],
            'payment_status' => ['required', 'in:unpaid,paid'],
            'payment_method' => ['nullable', 'required_if:payment_status,paid', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
            'total_price' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'picked_up_at' => ['nullable', 'date'],
            'returned_at' => ['nullable', 'date'],
        ]);

        $bike = Bike::findOrFail($data['bike_id']);
        $this->availability->validateOrFail(
            $bike,
            CarbonImmutable::parse($data['starts_at']),
            CarbonImmutable::parse($data['ends_at']),
            (int) $data['quantity'],
            $ignore instanceof Reservation ? $ignore : null
        );

        return $data;
    }

    private function calculateTotal(array $data): float
    {
        return $this->pricing->calculateTotalForRange(
            Bike::findOrFail($data['bike_id']),
            CarbonImmutable::parse($data['starts_at']),
            CarbonImmutable::parse($data['ends_at']),
            (int) $data['quantity'],
            'daily'
        );
    }

    private function syncReservationDaysFromRange(Reservation $reservation): void
    {
        $start = CarbonImmutable::parse($reservation->starts_at)->startOfDay();
        $end = CarbonImmutable::parse($reservation->ends_at)->startOfDay();
        $days = max(1, (int) $start->diffInDays($end) + 1);

        $reservation->days()->delete();

        foreach (range(0, $days - 1) as $offset) {
            $reservation->days()->create([
                'reservation_date' => $start->addDays($offset)->toDateString(),
            ]);
        }
    }

    private function formatValues(Reservation $reservation): array
    {
        return [
            ...$reservation->toArray(),
            'starts_at' => optional($reservation->starts_at)?->format('Y-m-d\TH:i'),
            'ends_at' => optional($reservation->ends_at)?->format('Y-m-d\TH:i'),
            'reservation_date' => optional($reservation->starts_at)?->format('Y-m-d'),
            'reservation_end_date' => optional($reservation->ends_at)?->format('Y-m-d'),
            'quantity' => $reservation->quantity ?? 1,
            'payment_status' => $reservation->payment_status ?? 'unpaid',
            'payment_method' => $reservation->payment_method,
            'paid_at' => optional($reservation->paid_at)?->format('Y-m-d\TH:i'),
            'picked_up_at' => optional($reservation->picked_up_at)?->format('Y-m-d\TH:i'),
            'returned_at' => optional($reservation->returned_at)?->format('Y-m-d\TH:i'),
            'total_price' => $reservation->total_price ?? 0,
        ];
    }

    private function fields(): array
    {
        return [
            ['name' => 'reservation_number', 'label' => 'Broj rezervacije', 'type' => 'text', 'required' => true],
            ['name' => 'user_id', 'label' => 'Korisnik', 'type' => 'select', 'required' => true, 'options' => User::orderBy('name')->pluck('name', 'id')->all()],
            ['name' => 'bike_id', 'label' => 'Bicikl', 'type' => 'select', 'required' => true, 'options' => Bike::orderBy('name')->pluck('name', 'id')->all()],
            ['name' => 'quantity', 'label' => 'Količina bicikala', 'type' => 'number', 'required' => true, 'step' => '1'],
            ['name' => 'station_id', 'label' => 'Stanica', 'type' => 'select', 'options' => Station::orderBy('name')->pluck('name', 'id')->all()],
            ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'required' => true, 'options' => [
                'pending' => 'Pending',
                'confirmed' => 'Confirmed',
                'active' => 'Active',
                'completed' => 'Completed',
                'cancelled' => 'Cancelled',
            ]],
            ['name' => 'payment_status', 'label' => 'Plaćanje', 'type' => 'select', 'required' => true, 'options' => [
                'unpaid' => 'Neplaćeno',
                'paid' => 'Plaćeno',
            ]],
            ['name' => 'payment_method', 'label' => 'Način plaćanja', 'type' => 'text', 'hint' => 'Kartica, gotovina, virman...'],
            ['name' => 'starts_at', 'label' => 'Početak', 'type' => 'datetime-local', 'required' => true],
            ['name' => 'ends_at', 'label' => 'Kraj', 'type' => 'datetime-local', 'required' => true],
            ['name' => 'total_price', 'label' => 'Ukupna cijena', 'type' => 'number', 'step' => '0.01', 'hint' => 'Može ostati 0 dok cijene ne uvedemo u logiku.'],
            ['name' => 'notes', 'label' => 'Napomena', 'type' => 'textarea'],
            ['name' => 'picked_up_at', 'label' => 'Preuzeto', 'type' => 'datetime-local'],
            ['name' => 'returned_at', 'label' => 'Vraćeno', 'type' => 'datetime-local'],
        ];
    }
}
