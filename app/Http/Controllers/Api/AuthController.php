<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\NewAccessToken;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => $request->password,
            'role' => User::ROLE_PENGGUNA,
        ]);

        return response()->json([
            'message' => 'Registration successful',
            'user' => $user,
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Alamat email atau kata sandi salah.'],
            ]);
        }

        if ($user->role !== User::ROLE_PENGGUNA) {
            throw ValidationException::withMessages([
                'email' => ['Akun ini tidak dapat digunakan untuk login di aplikasi mobile.'],
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Akun Anda telah dinonaktifkan.'],
            ]);
        }

        $token = $this->issueMobileToken($user);

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'access_token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->accessToken->expires_at?->toIso8601String(),
        ]);
    }

    private function issueMobileToken(User $user): NewAccessToken
    {
        $expirationMinutes = (int) config('sanctum.expiration', 10080);

        return $user->createToken(
            'mobile_auth_token',
            ['*'],
            now()->addMinutes($expirationMinutes),
        );
    }

    public function logout(Request $request)
    {
        $validated = $request->validate([
            'device_token' => ['nullable', 'string', 'max:4096'],
        ]);

        if (! empty($validated['device_token'])) {
            DeviceToken::query()
                ->where('user_id', $request->user()->id)
                ->where('token_hash', DeviceToken::hash($validated['device_token']))
                ->delete();
        }

        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }
}
