<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Station;

class StationController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => Station::withCount('bikes')->orderBy('name')->get(),
        ]);
    }
}
