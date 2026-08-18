<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bike;
use App\Models\Station;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class BikeController extends Controller
{
    public function index()
    {
        $bikes = Bike::with('station')->latest()->get();

        return view('admin.resources.index', [
            'title' => 'Bicikli',
            'subtitle' => 'CRUD za bicikle, veličine i lager.',
            'headers' => ['Šifra', 'Naziv', 'Veličina', 'Lager', 'Brzina', 'Oprema', 'Stanica', 'Tip', 'Status'],
            'createUrl' => route('admin.bikes.create'),
            'rows' => $bikes->map(fn (Bike $bike) => [
                'cells' => [
                    $bike->code,
                    $bike->name,
                    $bike->size ?? '-',
                    $bike->stock_quantity,
                    $bike->gear_count ? $bike->gear_count.' brzina' : '-',
                    $this->shortText($bike->equipment),
                    $bike->station?->name ?? '-',
                    $bike->type,
                    $bike->status,
                ],
                'editUrl' => route('admin.bikes.edit', $bike),
                'deleteUrl' => route('admin.bikes.destroy', $bike),
            ]),
        ]);
    }

    public function create()
    {
        return view('admin.resources.form', [
            'title' => 'Novi bicikl',
            'subtitle' => 'Dodaj novi bicikl u sustav.',
            'action' => route('admin.bikes.store'),
            'method' => 'POST',
            'backUrl' => route('admin.bikes.index'),
            'fields' => $this->fields(),
            'values' => [],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request);
        $data['code'] = $data['code'] ?: $this->generateCode($data['name']);
        $data['last_service_at'] = $data['last_service_at'] ?: null;
        $data['price_per_hour'] = 0;
        $data['battery_level'] = null;
        $data['gear_count'] = $data['gear_count'] ?? null;

        Bike::create($data);

        return redirect()->route('admin.bikes.index')->with('status', 'Bicikl je kreiran.');
    }

    public function edit(Bike $bike)
    {
        return view('admin.resources.form', [
            'title' => 'Uredi bicikl',
            'subtitle' => 'Promijeni podatke bicikla.',
            'action' => route('admin.bikes.update', $bike),
            'method' => 'PUT',
            'backUrl' => route('admin.bikes.index'),
            'fields' => $this->fields(),
            'values' => $bike->toArray(),
        ]);
    }

    public function update(Request $request, Bike $bike)
    {
        $data = $this->validateRequest($request, $bike->id);
        $data['code'] = $data['code'] ?: $this->generateCode($data['name']);
        $data['last_service_at'] = $data['last_service_at'] ?: null;
        $data['price_per_hour'] = 0;
        $data['battery_level'] = null;
        $data['gear_count'] = $data['gear_count'] ?? null;

        $bike->update($data);

        return redirect()->route('admin.bikes.index')->with('status', 'Bicikl je ažuriran.');
    }

    public function destroy(Bike $bike)
    {
        $bike->delete();

        return redirect()->route('admin.bikes.index')->with('status', 'Bicikl je obrisan.');
    }

    private function validateRequest(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'station_id' => ['nullable', 'exists:stations,id'],
            'code' => ['nullable', 'string', 'max:255', Rule::unique('bikes', 'code')->ignore($ignoreId)],
            'name' => ['required', 'string', 'max:255'],
            'size' => ['nullable', 'string', 'max:50'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'type' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', 'max:255'],
            'battery_level' => ['nullable', 'integer', 'min:0', 'max:100'],
            'gear_count' => ['nullable', 'integer', 'min:0', 'max:99'],
            'description' => ['nullable', 'string'],
            'equipment' => ['nullable', 'string'],
            'technical_details' => ['nullable', 'string'],
            'image_url' => ['nullable', 'string', 'max:255'],
            'last_service_at' => ['nullable', 'date'],
        ]);
    }

    private function shortText(?string $value): string
    {
        if (! $value) {
            return '-';
        }

        return Str::limit($value, 80);
    }

    private function generateCode(string $name): string
    {
        return Str::upper(Str::slug($name, '-')).'-'.Str::upper(Str::random(4));
    }

    private function fields(): array
    {
        return [
            ['name' => 'station_id', 'label' => 'Stanica', 'type' => 'select', 'options' => Station::orderBy('name')->pluck('name', 'id')->all()],
            ['name' => 'code', 'label' => 'Šifra', 'type' => 'text', 'hint' => 'Ako ostaviš prazno, generirat će se automatski.'],
            ['name' => 'name', 'label' => 'Naziv', 'type' => 'text', 'required' => true],
            ['name' => 'size', 'label' => 'Veličina', 'type' => 'text'],
            ['name' => 'stock_quantity', 'label' => 'Lager', 'type' => 'number', 'required' => true, 'step' => '1'],
            ['name' => 'gear_count', 'label' => 'Broj brzina', 'type' => 'number', 'step' => '1', 'hint' => 'Primjer: 21, 24, 27.'],
            ['name' => 'type', 'label' => 'Tip', 'type' => 'text', 'required' => true],
            ['name' => 'status', 'label' => 'Status', 'type' => 'text', 'required' => true],
            ['name' => 'description', 'label' => 'Opis', 'type' => 'textarea'],
            ['name' => 'equipment', 'label' => 'Oprema uz bicikl', 'type' => 'textarea', 'hint' => 'Primjer: kaciga, lokot, svjetla, set za popravak.'],
            ['name' => 'technical_details', 'label' => 'Tehnički detalji', 'type' => 'textarea', 'hint' => 'Upiši bitne tehničke podatke koji se trebaju vidjeti korisniku.'],
            ['name' => 'image_url', 'label' => 'Slika', 'type' => 'image', 'hint' => 'Odaberi uploadanu sliku ili ručno unesi URL.'],
            ['name' => 'last_service_at', 'label' => 'Zadnji servis', 'type' => 'date'],
        ];
    }
}
