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
            ],
            'values' => [
                'contact_email' => $settings['contact_email'] ?? 'rent@stupnik.bike',
                'terms_of_use' => $settings['terms_of_use'] ?? $this->defaultTermsOfUse(),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'contact_email' => ['required', 'email:rfc', 'max:255'],
            'terms_of_use' => ['required', 'string'],
        ]);

        $settings = Setting::value('app', []);
        $settings['contact_email'] = $data['contact_email'];
        $settings['terms_of_use'] = $data['terms_of_use'];

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
