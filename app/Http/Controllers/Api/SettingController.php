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
                'terms_of_use' => $settings['terms_of_use'] ?? null,
                'pickup_location' => [
                    'name' => $settings['pickup_location_name'] ?? null,
                    'address' => $settings['pickup_location_address'] ?? null,
                    'latitude' => isset($settings['pickup_location_latitude']) ? (float) $settings['pickup_location_latitude'] : null,
                    'longitude' => isset($settings['pickup_location_longitude']) ? (float) $settings['pickup_location_longitude'] : null,
                ],
            ],
        ]);
    }
}
