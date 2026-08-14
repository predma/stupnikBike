<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bike;
use App\Models\Issue;
use App\Models\Notification;
use App\Models\Reservation;
use App\Models\Station;
use App\Models\User;

class DashboardController extends Controller
{
    public function __invoke()
    {
        return view('admin.dashboard', [
            'counts' => [
                'stations' => Station::count(),
                'bikes' => Bike::count(),
                'reservations' => Reservation::count(),
                'issues' => Issue::count(),
                'notifications' => Notification::count(),
                'users' => User::count(),
            ],
            'recentBikes' => Bike::with('station')->latest()->take(5)->get(),
            'recentReservations' => Reservation::with(['user', 'bike'])->latest()->take(5)->get(),
            'recentIssues' => Issue::with(['bike', 'user'])->latest()->take(5)->get(),
        ]);
    }
}
