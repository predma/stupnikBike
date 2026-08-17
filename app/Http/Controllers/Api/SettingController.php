<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class SettingController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $settings = Setting::value('app', []);

        return response()->json([
            'data' => [
                'contact_email' => $settings['contact_email'] ?? null,
            ],
        ]);
    }
}
