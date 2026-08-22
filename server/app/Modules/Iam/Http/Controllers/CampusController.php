<?php

namespace App\Modules\Iam\Http\Controllers;

use App\Modules\Iam\Models\Campus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class CampusController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Campus::with(['organization', 'branches'])->get());
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(Campus::with(['organization', 'branches'])->findOrFail($id));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'organization_id' => 'required|uuid|exists:organizations,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:campuses,code',
            'address' => 'nullable|string|max:500',
            'postal_code' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ]);

        $campus = Campus::create($validated);
        return response()->json($campus, 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $campus = Campus::findOrFail($id);

        $validated = $request->validate([
            'name' => 'string|max:255',
            'code' => 'string|max:50|unique:campuses,code,' . $id,
            'address' => 'nullable|string|max:500',
            'postal_code' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ]);

        $campus->update($validated);
        return response()->json($campus);
    }

    public function destroy(string $id): JsonResponse
    {
        Campus::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}
