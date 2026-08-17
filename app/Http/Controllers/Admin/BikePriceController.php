<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bike;
use App\Models\BikePrice;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BikePriceController extends Controller
{
    public function index()
    {
        $prices = BikePrice::with('bike')->orderByDesc('effective_from')->latest('id')->get();

        return view('admin.resources.index', [
            'title' => 'Cjenik bicikala',
            'subtitle' => 'Cijene po biciklu i datumu od kada vrijede.',
            'headers' => ['Bicikl', 'Vrijedi od', 'Cijena', 'Tip obračuna', 'Aktivno', 'Napomena'],
            'createUrl' => route('admin.bike-prices.create'),
            'rows' => $prices->map(fn (BikePrice $price) => [
                'cells' => [
                    trim(($price->bike?->code ?? '').' '.$price->bike?->name.' '.($price->bike?->size ? '('.$price->bike?->size.')' : '')),
                    $price->effective_from?->format('d.m.Y.'),
                    number_format((float) $price->price, 2, ',', '.').' EUR',
                    $price->billing_type === 'daily' ? 'Dnevno' : $price->billing_type,
                    $price->is_active ? 'Da' : 'Ne',
                    $price->notes ?? '-',
                ],
                'editUrl' => route('admin.bike-prices.edit', $price),
                'deleteUrl' => route('admin.bike-prices.destroy', $price),
            ]),
        ]);
    }

    public function create()
    {
        return view('admin.resources.form', [
            'title' => 'Nova cijena',
            'subtitle' => 'Dodaj cijenu koja vrijedi od odabranog datuma.',
            'action' => route('admin.bike-prices.store'),
            'method' => 'POST',
            'backUrl' => route('admin.bike-prices.index'),
            'fields' => $this->fields(),
            'values' => [
                'billing_type' => 'daily',
                'is_active' => true,
                'effective_from' => now()->toDateString(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        BikePrice::create($this->validateRequest($request));

        return redirect()->route('admin.bike-prices.index')->with('status', 'Cijena je kreirana.');
    }

    public function edit(BikePrice $bikePrice)
    {
        return view('admin.resources.form', [
            'title' => 'Uredi cijenu',
            'subtitle' => $this->isHistorical($bikePrice)
                ? 'Cijena je već počela vrijediti. Možeš promijeniti iznos, aktivnost i napomenu; bicikl i datum ostaju zaključani.'
                : 'Uredi buduću cijenu prije nego počne vrijediti.',
            'action' => route('admin.bike-prices.update', $bikePrice),
            'method' => 'PUT',
            'backUrl' => route('admin.bike-prices.index'),
            'fields' => $this->fields($bikePrice),
            'values' => [
                ...$bikePrice->toArray(),
                'effective_from' => $bikePrice->effective_from?->format('Y-m-d'),
            ],
        ]);
    }

    public function update(Request $request, BikePrice $bikePrice)
    {
        if ($this->isHistorical($bikePrice)) {
            $bikePrice->update($this->validateHistoricalRequest($request));

            return redirect()->route('admin.bike-prices.index')->with('status', 'Cijena je ažurirana.');
        }

        $bikePrice->update($this->validateRequest($request, $bikePrice->id));

        return redirect()->route('admin.bike-prices.index')->with('status', 'Cijena je ažurirana.');
    }

    public function destroy(BikePrice $bikePrice)
    {
        if ($bikePrice->effective_from->isPast() && ! $bikePrice->effective_from->isToday()) {
            return redirect()->route('admin.bike-prices.index')->with('status', 'Prošle cijene se ne brišu. Deaktiviraj ih ako treba.');
        }

        $bikePrice->delete();

        return redirect()->route('admin.bike-prices.index')->with('status', 'Cijena je obrisana.');
    }

    private function validateRequest(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'bike_id' => ['required', 'exists:bikes,id'],
            'effective_from' => [
                'required',
                'date',
                Rule::unique('bike_prices', 'effective_from')
                    ->where('bike_id', $request->input('bike_id'))
                    ->where('billing_type', $request->input('billing_type', 'daily'))
                    ->ignore($ignoreId),
            ],
            'price' => ['required', 'numeric', 'min:0'],
            'billing_type' => ['required', 'in:daily'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    private function validateHistoricalRequest(Request $request): array
    {
        $data = $request->validate([
            'price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    private function isHistorical(BikePrice $bikePrice): bool
    {
        return $bikePrice->effective_from->isPast() || $bikePrice->effective_from->isToday();
    }

    private function fields(?BikePrice $bikePrice = null): array
    {
        $locked = $bikePrice && $this->isHistorical($bikePrice);

        return [
            ['name' => 'bike_id', 'label' => 'Bicikl', 'type' => 'select', 'required' => true, 'disabled' => $locked, 'hint' => $locked ? 'Zaključano jer je cijena već počela vrijediti.' : null, 'options' => Bike::orderBy('name')->orderBy('size')->get()->mapWithKeys(fn (Bike $bike) => [
                $bike->id => trim($bike->code.' · '.$bike->name.' '.($bike->size ? '('.$bike->size.')' : '')),
            ])->all()],
            ['name' => 'effective_from', 'label' => 'Vrijedi od', 'type' => 'date', 'required' => true, 'disabled' => $locked, 'hint' => $locked ? 'Za novi datum dodaj novu cijenu.' : null],
            ['name' => 'price', 'label' => 'Cijena', 'type' => 'number', 'step' => '0.01', 'required' => true],
            ['name' => 'billing_type', 'label' => 'Tip obračuna', 'type' => 'select', 'required' => true, 'disabled' => $locked, 'options' => [
                'daily' => 'Dnevno',
            ]],
            ['name' => 'notes', 'label' => 'Napomena', 'type' => 'textarea'],
            ['name' => 'is_active', 'label' => 'Aktivno', 'type' => 'checkbox'],
        ];
    }
}
