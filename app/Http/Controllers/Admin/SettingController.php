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
            ],
            'values' => [
                'contact_email' => $settings['contact_email'] ?? 'rent@stupnik.bike',
            ],
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'contact_email' => ['required', 'email:rfc', 'max:255'],
        ]);

        $settings = Setting::value('app', []);
        $settings['contact_email'] = $data['contact_email'];

        Setting::query()->updateOrCreate(
            ['key' => 'app'],
            ['value' => $settings]
        );

        return redirect()->route('admin.settings.edit')->with('status', 'Postavke su spremljene.');
    }
}
