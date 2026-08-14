<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bike;
use App\Models\Issue;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IssueController extends Controller
{
    public function index()
    {
        $issues = Issue::with(['bike', 'user', 'reservation'])->latest()->get();

        return view('admin.resources.index', [
            'title' => 'Kvarovi',
            'subtitle' => 'CRUD za servisne prijave i prioritet.',
            'headers' => ['Naslov', 'Korisnik', 'Bicikl', 'Rezervacija', 'Status', 'Prioritet'],
            'createUrl' => route('admin.issues.create'),
            'rows' => $issues->map(fn (Issue $issue) => [
                'cells' => [
                    $issue->title,
                    $issue->user?->name ?? '-',
                    $issue->bike?->name ?? '-',
                    $issue->reservation?->reservation_number ?? '-',
                    $issue->status,
                    $issue->priority,
                ],
                'editUrl' => route('admin.issues.edit', $issue),
                'deleteUrl' => route('admin.issues.destroy', $issue),
            ]),
        ]);
    }

    public function create()
    {
        return view('admin.resources.form', [
            'title' => 'Nova prijava kvara',
            'subtitle' => 'Dodaj servisnu prijavu ručno.',
            'action' => route('admin.issues.store'),
            'method' => 'POST',
            'backUrl' => route('admin.issues.index'),
            'fields' => $this->fields(),
            'values' => [],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request);
        Issue::create($data);

        return redirect()->route('admin.issues.index')->with('status', 'Kvar je kreiran.');
    }

    public function edit(Issue $issue)
    {
        return view('admin.resources.form', [
            'title' => 'Uredi kvar',
            'subtitle' => 'Promijeni status, prioritet ili opis.',
            'action' => route('admin.issues.update', $issue),
            'method' => 'PUT',
            'backUrl' => route('admin.issues.index'),
            'fields' => $this->fields(),
            'values' => $this->formatValues($issue),
        ]);
    }

    public function update(Request $request, Issue $issue)
    {
        $data = $this->validateRequest($request, $issue->id);
        $issue->update($data);

        return redirect()->route('admin.issues.index')->with('status', 'Kvar je ažuriran.');
    }

    public function destroy(Issue $issue)
    {
        $issue->delete();

        return redirect()->route('admin.issues.index')->with('status', 'Kvar je obrisan.');
    }

    private function validateRequest(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
            'bike_id' => ['nullable', 'exists:bikes,id'],
            'reservation_id' => ['nullable', 'exists:reservations,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'status' => ['required', 'string', 'max:255'],
            'priority' => ['required', 'string', 'max:255'],
            'image_url' => ['nullable', 'string', 'max:255'],
            'resolved_at' => ['nullable', 'date'],
        ]);

        return $data;
    }

    private function formatValues(Issue $issue): array
    {
        return [
            ...$issue->toArray(),
            'resolved_at' => optional($issue->resolved_at)?->format('Y-m-d\TH:i'),
        ];
    }

    private function fields(): array
    {
        return [
            ['name' => 'user_id', 'label' => 'Korisnik', 'type' => 'select', 'options' => User::orderBy('name')->pluck('name', 'id')->all()],
            ['name' => 'bike_id', 'label' => 'Bicikl', 'type' => 'select', 'options' => Bike::orderBy('name')->pluck('name', 'id')->all()],
            ['name' => 'reservation_id', 'label' => 'Rezervacija', 'type' => 'select', 'options' => Reservation::orderByDesc('id')->pluck('reservation_number', 'id')->all()],
            ['name' => 'title', 'label' => 'Naslov', 'type' => 'text', 'required' => true],
            ['name' => 'description', 'label' => 'Opis', 'type' => 'textarea', 'required' => true],
            ['name' => 'status', 'label' => 'Status', 'type' => 'text', 'required' => true],
            ['name' => 'priority', 'label' => 'Prioritet', 'type' => 'text', 'required' => true],
            ['name' => 'image_url', 'label' => 'URL slike', 'type' => 'text'],
            ['name' => 'resolved_at', 'label' => 'Riješeno', 'type' => 'datetime-local'],
        ];
    }
}
