<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\OrganizationLeader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Organization::with('leaders');

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        return response()->json($query->orderBy('name')->get());
    }

    public function show(Organization $organization): JsonResponse
    {
        return response()->json($organization->load('leaders'));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:yuqori,quyi,boshqa',
        ]);

        $organization = Organization::create($data);

        return response()->json($organization->load('leaders'), 201);
    }

    public function update(Request $request, Organization $organization): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:yuqori,quyi,boshqa',
        ]);

        $organization->update($data);

        return response()->json($organization->load('leaders'));
    }

    public function destroy(Organization $organization): JsonResponse
    {
        $organization->delete();

        return response()->json(['message' => 'Tashkilot o\'chirildi']);
    }

    public function leaders(Organization $organization): JsonResponse
    {
        return response()->json($organization->leaders);
    }

    public function storeLeader(Request $request, Organization $organization): JsonResponse
    {
        $data = $request->validate([
            'position' => 'required|string|max:255',
            'full_name' => 'required|string|max:255',
        ]);

        $leader = $organization->leaders()->create($data);

        return response()->json($leader, 201);
    }

    public function updateLeader(Request $request, Organization $organization, OrganizationLeader $leader): JsonResponse
    {
        $data = $request->validate([
            'position' => 'required|string|max:255',
            'full_name' => 'required|string|max:255',
        ]);

        $leader->update($data);

        return response()->json($leader);
    }

    public function destroyLeader(Organization $organization, OrganizationLeader $leader): JsonResponse
    {
        $leader->delete();

        return response()->json(['message' => 'Rahbar o\'chirildi']);
    }
}
