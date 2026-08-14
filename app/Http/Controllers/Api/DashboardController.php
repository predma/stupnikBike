<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bike;
use App\Models\Issue;
use App\Models\Notification;
use App\Models\Reservation;
use App\Models\Station;

class DashboardController extends Controller
{
    public function __invoke()
    {
        return response()->json([
            'stats' => [
                'stations' => Station::count(),
                'bikes' => Bike::count(),
                'available_bikes' => Bike::where('status', 'available')->count(),
                'reservations' => Reservation::count(),
                'issues' => Issue::count(),
                'notifications' => Notification::count(),
            ],
            'recent_bikes' => Bike::with('station')->latest()->take(5)->get(),
            'recent_reservations' => Reservation::with(['bike', 'user'])->latest()->take(5)->get(),
        ]);
    }
}
