<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Station;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StationController extends Controller
{
    public function index()
    {
        $stations = Station::withCount(['bikes', 'reservations'])->latest()->get();

        return view('admin.resources.index', [
            'title' => 'Stanice',
            'subtitle' => 'CRUD za stanice najma i kapacitete.',
            'headers' => ['Naziv', 'Grad', 'Adresa', 'Aktivna', 'Bicikli', 'Rezervacije'],
            'createUrl' => route('admin.stations.create'),
            'rows' => $stations->map(fn (Station $station) => [
                'cells' => [
                    $station->name,
                    $station->city,
                    $station->address,
                    $station->is_active ? 'Da' : 'Ne',
                    $station->bikes_count,
                    $station->reservations_count,
                ],
                'editUrl' => route('admin.stations.edit', $station),
                'deleteUrl' => route('admin.stations.destroy', $station),
            ]),
        ]);
    }

    public function create()
    {
        return view('admin.resources.form', [
            'title' => 'Nova stanica',
            'subtitle' => 'Dodaj novu lokaciju za najam bicikala.',
            'action' => route('admin.stations.store'),
            'method' => 'POST',
            'backUrl' => route('admin.stations.index'),
            'fields' => $this->fields(),
            'values' => [],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:stations,slug'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $data['is_active'] = $request->boolean('is_active');

        Station::create($data);

        return redirect()->route('admin.stations.index')->with('status', 'Stanica je kreirana.');
    }

    public function edit(Station $station)
    {
        return view('admin.resources.form', [
            'title' => 'Uredi stanicu',
            'subtitle' => 'Promijeni podatke stanice.',
            'action' => route('admin.stations.update', $station),
            'method' => 'PUT',
            'backUrl' => route('admin.stations.index'),
            'fields' => $this->fields(),
            'values' => $station->toArray(),
        ]);
    }

    public function update(Request $request, Station $station)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:stations,slug,'.$station->id],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $data['is_active'] = $request->boolean('is_active');

        $station->update($data);

        return redirect()->route('admin.stations.index')->with('status', 'Stanica je ažurirana.');
    }

    public function destroy(Station $station)
    {
        $station->delete();

        return redirect()->route('admin.stations.index')->with('status', 'Stanica je obrisana.');
    }

    private function fields(): array
    {
        return [
            ['name' => 'name', 'label' => 'Naziv', 'type' => 'text', 'required' => true],
            ['name' => 'slug', 'label' => 'Slug', 'type' => 'text', 'hint' => 'Ako ostaviš prazno, generirat će se automatski.'],
            ['name' => 'address', 'label' => 'Adresa', 'type' => 'text', 'required' => true],
            ['name' => 'city', 'label' => 'Grad', 'type' => 'text', 'required' => true],
            ['name' => 'latitude', 'label' => 'Latitude', 'type' => 'text'],
            ['name' => 'longitude', 'label' => 'Longitude', 'type' => 'text'],
            ['name' => 'description', 'label' => 'Opis', 'type' => 'textarea'],
            ['name' => 'is_active', 'label' => 'Aktivna', 'type' => 'checkbox'],
        ];
    }
}
