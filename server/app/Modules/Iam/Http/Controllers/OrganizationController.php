<?php

namespace App\Modules\Iam\Http\Controllers;

use App\Modules\Iam\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class OrganizationController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Organization::with('campuses')->get());
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(Organization::with('campuses')->findOrFail($id));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $org = Organization::create($validated);
        return response()->json($org, 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $org = Organization::findOrFail($id);

        $validated = $request->validate([
            'name' => 'string|max:255',
        ]);

        $org->update($validated);
        return response()->json($org);
    }

    public function destroy(string $id): JsonResponse
    {
        Organization::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}
