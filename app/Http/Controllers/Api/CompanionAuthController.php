<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CompanionAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
            'device_name' => 'required|string|max:100',
        ]);

        $user = User::query()
            ->where('email', $validated['login'])
            ->orWhere('phone_number', $validated['login'])
            ->first();

        if (! $user || ! $user->is_active || ! Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'بيانات الدخول غير صحيحة.'], 422);
        }

        $token = $user->createToken(
            'android-'.$validated['device_name'],
            ['*'],
            now()->addDays(90),
        );

        return response()->json([
            'token' => $token->plainTextToken,
            'expires_at' => $token->accessToken->expires_at,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'تم تسجيل الخروج من الجهاز.']);
    }
}
