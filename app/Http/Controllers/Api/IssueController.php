<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Issue;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;

class IssueController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => Issue::with(['bike', 'user', 'reservation'])
                ->where('user_id', request()->user()->id)
                ->latest()
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'bike_id' => ['required', 'exists:bikes,id'],
            'reservation_id' => ['nullable', 'exists:reservations,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'priority' => ['nullable', 'in:low,normal,high,urgent'],
            'photo' => ['required', 'image', 'max:5120'],
        ]);

        $photoPath = $request->file('photo')->store('issues', 'public');

        $issue = Issue::create([
            'user_id' => $request->user()->id,
            'bike_id' => $data['bike_id'],
            'reservation_id' => $data['reservation_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'],
            'status' => 'open',
            'priority' => $data['priority'] ?? 'normal',
            'image_url' => '/storage/' . $photoPath,
        ]);

        Notification::create([
            'user_id' => $request->user()->id,
            'type' => 'issue',
            'title' => 'Kvar prijavljen',
            'body' => sprintf('Kvar "%s" je zaprimljen i označen kao otvoren.', $issue->title),
            'channel' => 'app',
            'is_read' => false,
        ]);

        User::query()
            ->where('role', 'admin')
            ->where('is_active', true)
            ->get()
            ->each(fn (User $admin) => Notification::create([
                'user_id' => $admin->id,
                'type' => 'issue',
                'title' => 'Nova prijava kvara',
                'body' => sprintf(
                    '%s je prijavio kvar "%s" za bicikl %s.',
                    $request->user()->name,
                    $issue->title,
                    $issue->bike?->code ?? "#{$issue->bike_id}"
                ),
                'channel' => 'app',
                'is_read' => false,
            ]));

        return response()->json([
            'data' => $issue->load(['bike', 'user', 'reservation']),
        ], 201);
    }
}
