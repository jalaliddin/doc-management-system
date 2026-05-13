<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Department::orderBy('name')->get());
    }

    public function show(Department $department): JsonResponse
    {
        return response()->json($department);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'index_code' => 'required|string|max:20',
            'head_name' => 'required|string|max:255',
            'head_phone' => 'nullable|string|max:20',
        ]);

        $department = Department::create($data);

        return response()->json($department, 201);
    }

    public function update(Request $request, Department $department): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'index_code' => 'required|string|max:20',
            'head_name' => 'required|string|max:255',
            'head_phone' => 'nullable|string|max:20',
        ]);

        $department->update($data);

        return response()->json($department);
    }

    public function destroy(Department $department): JsonResponse
    {
        $department->delete();

        return response()->json(['message' => 'Bo\'lim o\'chirildi']);
    }
}
