<?php

namespace App\Http\Controllers;

use App\Models\AdminUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $admin = AdminUser::where('username', $request->username)->first();

        if (! $admin || ! Hash::check($request->password, $admin->password)) {
            return response()->json(['message' => 'Login yoki parol noto\'g\'ri'], 401);
        }

        $token = $admin->createToken('admin-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'admin' => ['id' => $admin->id, 'username' => $admin->username],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Muvaffaqiyatli chiqildi']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['admin' => $request->user()]);
    }
}
