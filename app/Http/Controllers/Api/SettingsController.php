<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function show($key)
    {
        $setting = SiteSetting::where('key', $key)->first();
        return response()->json($setting ? $setting->value : null);
    }

    public function update(Request $request, $key)
    {
        $user = $request->user();
        if ($user->role !== 'admin' && $user->role !== 'owner') {
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
