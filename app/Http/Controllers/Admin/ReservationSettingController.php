<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bike;
use App\Models\ReservationSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReservationSettingController extends Controller
{
    public function index()
    {
        $settings = ReservationSetting::with('bikes')->latest('effective_from')->latest('id')->get();

        return view('admin.resources.index', [
            'title' => 'Rezervacijske postavke',
            'subtitle' => 'Pravila za dnevne i satne rezervacije po biciklima.',
            'headers' => ['Naziv', 'Način', 'Vrijedi od', 'Max dana', 'Bicikli', 'Turnusi', 'Aktivno'],
            'createUrl' => route('admin.reservation-settings.create'),
            'rows' => $settings->map(fn (ReservationSetting $setting) => [
                'cells' => [
                    $setting->name,
                    $setting->mode === 'hourly' ? 'Po satu' : 'Dnevno',
                    $setting->effective_from?->format('d.m.Y.'),
                    $setting->max_days_per_reservation,
                    $setting->bikes->pluck('name')->implode(', ') ?: '-',
                    $setting->isHourly() ? count($setting->normalizedSlots()) : 'N/A',
                    $setting->is_active ? 'Da' : 'Ne',
                ],
                'editUrl' => route('admin.reservation-settings.edit', $setting),
                'deleteUrl' => route('admin.reservation-settings.destroy', $setting),
            ]),
        ]);
    }

    public function create()
    {
        return view('admin.resources.form', [
            'title' => 'Nova rezervacijska postavka',
            'subtitle' => 'Definiraj od kada vrijedi i za koje bicikle.',
            'action' => route('admin.reservation-settings.store'),
            'method' => 'POST',
            'backUrl' => route('admin.reservation-settings.index'),
            'fields' => $this->fields(),
            'values' => ['max_days_per_reservation' => 1, 'is_active' => true],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request);
        $setting = ReservationSetting::create([
            'name' => $data['name'],
            'mode' => $data['mode'],
            'effective_from' => $data['effective_from'],
            'max_days_per_reservation' => $data['max_days_per_reservation'],
            'slots' => $this->parseSlots($data['slots_input'] ?? ''),
            'is_active' => $request->boolean('is_active'),
            'notes' => $data['notes'] ?? null,
        ]);

        $setting->bikes()->sync($data['bike_ids'] ?? []);

        return redirect()->route('admin.reservation-settings.index')->with('status', 'Rezervacijska postavka je kreirana.');
    }

    public function edit(ReservationSetting $reservationSetting)
    {
        return view('admin.resources.form', [
            'title' => 'Uredi rezervacijsku postavku',
            'subtitle' => 'Promijeni pravila i bicikle na koje vrijedi.',
            'action' => route('admin.reservation-settings.update', $reservationSetting),
            'method' => 'PUT',
            'backUrl' => route('admin.reservation-settings.index'),
            'fields' => $this->fields(),
            'values' => [
                ...$reservationSetting->toArray(),
                'bike_ids' => $reservationSetting->bikes->pluck('id')->all(),
                'slots_input' => $this->formatSlots($reservationSetting->slots ?? []),
            ],
        ]);
    }

    public function update(Request $request, ReservationSetting $reservationSetting)
    {
        $data = $this->validateRequest($request);
        $reservationSetting->update([
            'name' => $data['name'],
            'mode' => $data['mode'],
            'effective_from' => $data['effective_from'],
            'max_days_per_reservation' => $data['max_days_per_reservation'],
            'slots' => $this->parseSlots($data['slots_input'] ?? ''),
            'is_active' => $request->boolean('is_active'),
            'notes' => $data['notes'] ?? null,
        ]);

        $reservationSetting->bikes()->sync($data['bike_ids'] ?? []);

        return redirect()->route('admin.reservation-settings.index')->with('status', 'Rezervacijska postavka je ažurirana.');
    }

    public function destroy(ReservationSetting $reservationSetting)
    {
        $reservationSetting->delete();

        return redirect()->route('admin.reservation-settings.index')->with('status', 'Rezervacijska postavka je obrisana.');
    }

    private function validateRequest(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'mode' => ['required', 'in:daily,hourly'],
            'effective_from' => ['required', 'date'],
            'max_days_per_reservation' => ['required', 'integer', 'min:1', 'max:365'],
            'bike_ids' => ['required', 'array', 'min:1'],
            'bike_ids.*' => ['exists:bikes,id'],
            'slots_input' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    private function parseSlots(string $input): array
    {
        $lines = preg_split('/\R+/', trim($input)) ?: [];

        return collect($lines)
            ->map(fn (string $line) => trim($line))
            ->filter()
            ->map(function (string $line) {
                if (! str_contains($line, '-')) {
                    return null;
                }

                [$start, $end] = array_map('trim', explode('-', $line, 2));

                if (! preg_match('/^\d{2}:\d{2}$/', $start) || ! preg_match('/^\d{2}:\d{2}$/', $end)) {
                    return null;
                }

                return ['start' => $start, 'end' => $end];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function formatSlots(array $slots): string
    {
        return collect($slots)
            ->map(function ($slot) {
                if (is_string($slot)) {
                    return $slot;
                }

                $start = $slot['start'] ?? null;
                $end = $slot['end'] ?? null;

                return $start && $end ? "{$start}-{$end}" : null;
            })
            ->filter()
            ->implode(PHP_EOL);
    }

    private function fields(): array
    {
        return [
            ['name' => 'name', 'label' => 'Naziv', 'type' => 'text', 'required' => true],
            ['name' => 'mode', 'label' => 'Način rezervacije', 'type' => 'select', 'required' => true, 'options' => [
                'daily' => 'Dnevno',
                'hourly' => 'Po satu',
            ]],
            ['name' => 'effective_from', 'label' => 'Vrijedi od', 'type' => 'date', 'required' => true],
            ['name' => 'max_days_per_reservation', 'label' => 'Max dana po rezervaciji', 'type' => 'number', 'required' => true, 'hint' => 'Za dnevne rezervacije korisnik ne može odabrati više od ovog broja dana. Za satne ostavi 1.'],
            ['name' => 'bike_ids', 'label' => 'Bicikli', 'type' => 'multiselect', 'required' => true, 'options' => Bike::orderBy('name')->pluck('name', 'id')->all()],
            ['name' => 'slots_input', 'label' => 'Turnusi', 'type' => 'textarea', 'hint' => 'Za satni način upiši jedan turnus po retku u formatu 08:00-10:00. Za dnevni način ostavi prazno.'],
            ['name' => 'notes', 'label' => 'Napomena', 'type' => 'textarea'],
            ['name' => 'is_active', 'label' => 'Aktivno', 'type' => 'checkbox'],
        ];
    }
}
