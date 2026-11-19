<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ApiResponse;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\UpdateUserRequest;
use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use JsonException;
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

    public function changePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', 'min:8', 'different:current_password'],
        ]);

        $user = $request->user();

        if (! Hash::check($validated['current_password'], $user->password)) {
            return ApiResponse::error('Current password is incorrect.', 422);
        }

        $currentToken = $user->currentAccessToken();
        $user->forceFill(['password' => $validated['password']])->save();

        if ($currentToken instanceof PersonalAccessToken) {
            $user->tokens()->where('id', '!=', $currentToken->getKey())->delete();
        }

        return ApiResponse::success(null, 'Password changed successfully.');
    }

    public function updateProfile(UpdateUserRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = $request->user();
        foreach ($validated as $field => $value) {
            $user->{$field} = $value;
        }
        $user->save();

        return ApiResponse::success($user->fresh(), 'Profile updated successfully.');
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

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $status = Password::sendResetLink($validated);

        if ($status === Password::RESET_THROTTLED) {
            return ApiResponse::error('Please wait before requesting another password reset link.', 429);
        }

        if (in_array($status, [Password::RESET_LINK_SENT, Password::INVALID_USER], true)) {
            return ApiResponse::success(
                null,
                'If the account exists, a password reset link will be sent.',
            );
        }

        return ApiResponse::error('Unable to send a password reset link.', 500);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $resetData = json_decode(
                Crypt::decryptString($validated['token']),
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
        } catch (DecryptException|JsonException) {
            return ApiResponse::error('The password reset link is invalid or expired.', 422);
        }

        if (
            ! is_array($resetData)
            || ! is_string($resetData['email'] ?? null)
            || filter_var($resetData['email'], FILTER_VALIDATE_EMAIL) === false
            || ! is_string($resetData['token'] ?? null)
        ) {
            return ApiResponse::error('The password reset link is invalid or expired.', 422);
        }

        $status = Password::reset(
            [
                'email' => $resetData['email'],
                'token' => $resetData['token'],
                'password' => $validated['password'],
                'password_confirmation' => $validated['password_confirmation'],
            ],
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                ])->save();

                $user->tokens()->delete();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return ApiResponse::success(null, 'Password reset successfully.');
        }

        if ($status === Password::RESET_THROTTLED) {
            return ApiResponse::error('Please wait before trying to reset your password again.', 429);
        }

        if (in_array($status, [Password::INVALID_TOKEN, Password::INVALID_USER], true)) {
            return ApiResponse::error('The password reset link is invalid or expired.', 422);
        }

        return ApiResponse::error('Unable to reset the password.', 500);
    }
}
