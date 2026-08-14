<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Notification::with('user')->latest()->get();

        return view('admin.resources.index', [
            'title' => 'Obavijesti',
            'subtitle' => 'CRUD za push i in-app poruke.',
            'headers' => ['Naslov', 'Korisnik', 'Tip', 'Kanal', 'Pročitano'],
            'createUrl' => route('admin.notifications.create'),
            'rows' => $notifications->map(fn (Notification $notification) => [
                'cells' => [
                    $notification->title,
                    $notification->user?->name ?? '-',
                    $notification->type,
                    $notification->channel,
                    $notification->is_read ? 'Da' : 'Ne',
                ],
                'editUrl' => route('admin.notifications.edit', $notification),
                'deleteUrl' => route('admin.notifications.destroy', $notification),
            ]),
        ]);
    }

    public function create()
    {
        return view('admin.resources.form', [
            'title' => 'Nova obavijest',
            'subtitle' => 'Pošalji novu poruku korisniku.',
            'action' => route('admin.notifications.store'),
            'method' => 'POST',
            'backUrl' => route('admin.notifications.index'),
            'fields' => $this->fields(),
            'values' => [],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request);
        $data['is_read'] = $request->boolean('is_read');
        Notification::create($data);

        return redirect()->route('admin.notifications.index')->with('status', 'Obavijest je kreirana.');
    }

    public function edit(Notification $notification)
    {
        return view('admin.resources.form', [
            'title' => 'Uredi obavijest',
            'subtitle' => 'Promijeni sadržaj ili status pročitano.',
            'action' => route('admin.notifications.update', $notification),
            'method' => 'PUT',
            'backUrl' => route('admin.notifications.index'),
            'fields' => $this->fields(),
            'values' => $notification->toArray(),
        ]);
    }

    public function update(Request $request, Notification $notification)
    {
        $data = $this->validateRequest($request);
        $data['is_read'] = $request->boolean('is_read');
        $notification->update($data);

        return redirect()->route('admin.notifications.index')->with('status', 'Obavijest je ažurirana.');
    }

    public function destroy(Notification $notification)
    {
        $notification->delete();

        return redirect()->route('admin.notifications.index')->with('status', 'Obavijest je obrisana.');
    }

    private function validateRequest(Request $request): array
    {
        return $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
            'type' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'channel' => ['required', 'string', 'max:255'],
            'is_read' => ['sometimes', 'boolean'],
        ]);
    }

    private function fields(): array
    {
        return [
            ['name' => 'user_id', 'label' => 'Korisnik', 'type' => 'select', 'options' => User::orderBy('name')->pluck('name', 'id')->all()],
            ['name' => 'type', 'label' => 'Tip', 'type' => 'text', 'required' => true],
            ['name' => 'title', 'label' => 'Naslov', 'type' => 'text', 'required' => true],
            ['name' => 'body', 'label' => 'Poruka', 'type' => 'textarea', 'required' => true],
            ['name' => 'channel', 'label' => 'Kanal', 'type' => 'text', 'required' => true],
            ['name' => 'is_read', 'label' => 'Pročitano', 'type' => 'checkbox'],
        ];
    }
}
