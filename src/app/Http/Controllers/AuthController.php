<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()->where('email', $credentials['email'])->first();
        // echo $user->password;
        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return ApiResponse::error('Invalid credentials.', 401);
        }

        return ApiResponse::success([
            'token' => $user->createToken('api')->plainTextToken,
            'token_type' => 'Bearer',
            'user' => User::query()->with('roles')->find($user->id),
        ], 'Login successful.');
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return ApiResponse::success(null, 'Logged out successfully.');
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $currentUser = User::query()->with('roles')->find($user->id);
        return ApiResponse::success([
            'user' => $currentUser,
            'is_admin' => $currentUser->roles()->where('slug', 'admin')->exists(),
        ], 'Current user retrieved successfully.');
    }
}
