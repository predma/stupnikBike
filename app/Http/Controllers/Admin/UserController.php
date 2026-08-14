<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::latest()->get();

        return view('admin.resources.index', [
            'title' => 'Korisnici',
            'subtitle' => 'CRUD za korisnike i administratorske račune.',
            'headers' => ['Ime', 'Email', 'Uloga', 'Telefon', 'Aktivan'],
            'createUrl' => route('admin.users.create'),
            'rows' => $users->map(fn (User $user) => [
                'cells' => [
                    $user->name,
                    $user->email,
                    $user->role,
                    $user->phone ?? '-',
                    $user->is_active ? 'Da' : 'Ne',
                ],
                'editUrl' => route('admin.users.edit', $user),
                'deleteUrl' => route('admin.users.destroy', $user),
            ]),
        ]);
    }

    public function create()
    {
        return view('admin.resources.form', [
            'title' => 'Novi korisnik',
            'subtitle' => 'Dodaj korisnika ili admin račun.',
            'action' => route('admin.users.store'),
            'method' => 'POST',
            'backUrl' => route('admin.users.index'),
            'fields' => $this->fields(),
            'values' => [],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request);
        $data['password'] = Hash::make($data['password']);
        $data['is_active'] = $request->boolean('is_active');

        User::create($data);

        return redirect()->route('admin.users.index')->with('status', 'Korisnik je kreiran.');
    }

    public function edit(User $user)
    {
        return view('admin.resources.form', [
            'title' => 'Uredi korisnika',
            'subtitle' => 'Promijeni ulogu, kontakt ili aktivnost.',
            'action' => route('admin.users.update', $user),
            'method' => 'PUT',
            'backUrl' => route('admin.users.index'),
            'fields' => $this->fields(editing: true),
            'values' => $user->toArray(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $this->validateRequest($request, $user->id, true);
        $data['is_active'] = $request->boolean('is_active');

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('admin.users.index')->with('status', 'Korisnik je ažuriran.');
    }

    public function destroy(User $user)
    {
        if (auth()->id() === $user->id) {
            return redirect()->route('admin.users.index')->with('status', 'Ne možeš obrisati vlastiti račun.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('status', 'Korisnik je obrisan.');
    }

    private function validateRequest(Request $request, ?int $ignoreId = null, bool $editing = false): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($ignoreId)],
            'role' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];

        if ($editing) {
            $rules['password'] = ['nullable', 'string', 'min:6'];
        } else {
            $rules['password'] = ['required', 'string', 'min:6'];
        }

        return $request->validate($rules);
    }

    private function fields(bool $editing = false): array
    {
        $passwordField = ['name' => 'password', 'label' => $editing ? 'Nova lozinka' : 'Lozinka', 'type' => 'password', 'required' => ! $editing];
        if ($editing) {
            $passwordField['hint'] = 'Ostavi prazno ako želiš zadržati trenutnu lozinku.';
        }

        return [
            ['name' => 'name', 'label' => 'Ime', 'type' => 'text', 'required' => true],
            ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true],
            $passwordField,
            ['name' => 'role', 'label' => 'Uloga', 'type' => 'text', 'required' => true],
            ['name' => 'phone', 'label' => 'Telefon', 'type' => 'text'],
            ['name' => 'is_active', 'label' => 'Aktivan', 'type' => 'checkbox'],
        ];
    }
}
