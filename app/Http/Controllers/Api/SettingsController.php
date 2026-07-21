<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function show(Request $request, $key)
    {
        $setting = SiteSetting::where('key', $key)->first();
        if (!$setting || !$setting->value) {
            return response()->json(null);
        }

        $value = $setting->value;

        // Check if caller is an authenticated admin/owner using Sanctum
        $user = $request->user('sanctum');
        $isAdmin = $user && in_array($user->role, ['admin', 'owner']);

        // If unauthenticated or public request, strip out all sensitive and analytics/tracking IDs
        if (!$isAdmin && is_array($value)) {
            $sensitiveKeys = [
                'googleAnalyticsId',
                'googleTagManagerId',
                'clarityId',
                'mailHost',
                'mailPassword',
                'mailUsername',
                'smtpPassword',
                'secret',
                'apiKey',
                'token',
                'privateKey'
            ];
            foreach ($sensitiveKeys as $sKey) {
                unset($value[$sKey]);
            }
        }

        return response()->json($value);
    }

    public function update(Request $request, $key)
    {
        $user = $request->user();
        if (!$user || !in_array($user->role, ['admin', 'owner'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'value' => 'nullable|array',
        ]);

        $setting = SiteSetting::updateOrCreate(
            ['key' => $key],
            ['value' => $request->value]
        );

        return response()->json($setting->value);
    }
}
