<?php

namespace App\Modules\PlatformServices\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{
    /**
     * Get all system settings
     */
    public function index(): JsonResponse
    {
        $settings = DB::table('system_settings')->get()->mapWithKeys(function ($row) {
            return [$row->key => $row->value];
        });

        return response()->json($settings);
    }

    /**
     * Get a single setting
     */
    public function show(string $key): JsonResponse
    {
        $value = DB::table('system_settings')->where('key', $key)->value('value');

        if ($value === null) {
            return response()->json(['message' => 'Setting not found'], 404);
        }

        return response()->json(['key' => $key, 'value' => $value]);
    }

    /**
     * Update a setting
     */
    public function update(Request $request, string $key): JsonResponse
    {
        $validated = $request->validate([
            'value' => 'required|string',
        ]);

        $exists = DB::table('system_settings')->where('key', $key)->exists();

        if ($exists) {
            DB::table('system_settings')->where('key', $key)->update([
                'value' => $validated['value'],
                'updated_at' => now(),
            ]);
        } else {
            DB::table('system_settings')->insert([
                'key' => $key,
                'value' => $validated['value'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json(['key' => $key, 'value' => $validated['value']]);
    }

    /**
     * Batch update multiple settings
     */
    public function batchUpdate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*' => 'string',
        ]);

        foreach ($validated['settings'] as $key => $value) {
            $exists = DB::table('system_settings')->where('key', $key)->exists();
            if ($exists) {
                DB::table('system_settings')->where('key', $key)->update([
                    'value' => $value,
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('system_settings')->insert([
                    'key' => $key,
                    'value' => $value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return response()->json(['message' => 'Settings updated', 'count' => count($validated['settings'])]);
    }
}
