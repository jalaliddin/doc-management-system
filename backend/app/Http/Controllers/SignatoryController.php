<?php

namespace App\Http\Controllers;

use App\Models\Signatory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SignatoryController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Signatory::orderBy('id')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'position' => 'required|string|max:255',
            'full_name' => 'required|string|max:255',
            'is_active' => 'boolean',
        ]);

        $signatory = Signatory::create($data);

        return response()->json($signatory, 201);
    }

    public function update(Request $request, Signatory $signatory): JsonResponse
    {
        $data = $request->validate([
            'position' => 'required|string|max:255',
            'full_name' => 'required|string|max:255',
            'is_active' => 'boolean',
        ]);

        $signatory->update($data);

        return response()->json($signatory);
    }

    public function destroy(Signatory $signatory): JsonResponse
    {
        $signatory->delete();

        return response()->json(['message' => 'Imzolovchi o\'chirildi']);
    }
}
