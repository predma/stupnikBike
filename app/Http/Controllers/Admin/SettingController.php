<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function edit()
    {
        $settings = Setting::value('app', []);

        return view('admin.resources.form', [
            'title' => 'Postavke',
            'subtitle' => 'Opće postavke koje mobilna aplikacija povlači s backenda.',
            'action' => route('admin.settings.update'),
            'method' => 'PUT',
            'backUrl' => route('admin.dashboard'),
            'fields' => [
                [
                    'name' => 'contact_email',
                    'label' => 'Kontakt email za korisnike',
                    'type' => 'email',
                    'required' => true,
                    'hint' => 'Prikazuje se korisniku u detalju rezervacije.',
                ],
                [
                    'name' => 'terms_of_use',
                    'label' => 'Uvjeti korištenja',
                    'type' => 'textarea',
                    'required' => true,
                    'hint' => 'Prikazuje se u mobilnoj aplikaciji kada korisnik klikne Uvjete korištenja.',
                ],
                [
                    'name' => 'pickup_location_name',
                    'label' => 'Naziv lokacije preuzimanja',
                    'type' => 'text',
                    'required' => true,
                    'hint' => 'Prikazuje se na naslovnici iznad bicikala.',
                ],
                [
                    'name' => 'pickup_location_address',
                    'label' => 'Adresa/opis lokacije',
                    'type' => 'text',
                    'required' => true,
                ],
                [
                    'name' => 'pickup_location_latitude',
                    'label' => 'Latitude',
                    'type' => 'number',
                    'required' => true,
                    'step' => '0.000000000000001',
                ],
                [
                    'name' => 'pickup_location_longitude',
                    'label' => 'Longitude',
                    'type' => 'number',
                    'required' => true,
                    'step' => '0.000000000000001',
                ],
            ],
            'values' => [
                'contact_email' => $settings['contact_email'] ?? 'rent@stupnik.bike',
                'terms_of_use' => $settings['terms_of_use'] ?? $this->defaultTermsOfUse(),
                'pickup_location_name' => $settings['pickup_location_name'] ?? 'Lokacija preuzimanja',
                'pickup_location_address' => $settings['pickup_location_address'] ?? 'Stupnik Bike',
                'pickup_location_latitude' => $settings['pickup_location_latitude'] ?? '45.167915695679504',
                'pickup_location_longitude' => $settings['pickup_location_longitude'] ?? '17.800039051188474',
            ],
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'contact_email' => ['required', 'email:rfc', 'max:255'],
            'terms_of_use' => ['required', 'string'],
            'pickup_location_name' => ['required', 'string', 'max:255'],
            'pickup_location_address' => ['required', 'string', 'max:255'],
            'pickup_location_latitude' => ['required', 'numeric', 'between:-90,90'],
            'pickup_location_longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $settings = Setting::value('app', []);
        $settings['contact_email'] = $data['contact_email'];
        $settings['terms_of_use'] = $data['terms_of_use'];
        $settings['pickup_location_name'] = $data['pickup_location_name'];
        $settings['pickup_location_address'] = $data['pickup_location_address'];
        $settings['pickup_location_latitude'] = (float) $data['pickup_location_latitude'];
        $settings['pickup_location_longitude'] = (float) $data['pickup_location_longitude'];

        Setting::query()->updateOrCreate(
            ['key' => 'app'],
            ['value' => $settings]
        );

        return redirect()->route('admin.settings.edit')->with('status', 'Postavke su spremljene.');
    }

    private function defaultTermsOfUse(): string
    {
        return "Korištenjem aplikacije Stupnik Bike korisnik potvrđuje da će bicikl koristiti odgovorno, u skladu s prometnim propisima i u dogovorenom terminu rezervacije.\n\nKorisnik je dužan vratiti bicikl u stanju u kojem ga je preuzeo te odmah prijaviti svaki kvar ili oštećenje kroz aplikaciju.\n\nOpćina Stupnik i administrator sustava mogu odbiti ili otkazati rezervaciju u slučaju zlouporabe, netočnih podataka ili nedostupnosti bicikla.";
    }
}
