<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class ProfileController extends Controller
{
    public function update(Request $request)
    {
        abort_unless($request->user()->isPengguna(), 403);
        foreach (['name', 'email', 'phone'] as $field) {
            if (is_string($request->input($field))) {
                $value = trim($request->input($field));
                if ($field === 'phone') {
                    $value = preg_replace('/[\s().-]+/', '', $value);
                }
                $request->merge([$field => $value === '' && $field === 'phone' ? null : $value]);
            }
        }
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($request->user()->id)],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9]{8,15}$/'],
            'current_password' => ['nullable', 'string', 'max:128'],
            'role' => ['prohibited'], 'is_active' => ['prohibited'],
            'password' => ['prohibited'], 'user_id' => ['prohibited'],
        ], ['phone.regex' => 'Nomor telepon harus berisi 8–15 digit dan boleh diawali tanda +.']);

        try {
            $user = DB::transaction(function () use ($request, $validated) {
                $user = User::query()->lockForUpdate()->findOrFail($request->user()->id);
                abort_unless($user->isPengguna() && $user->is_active, 403);
                if (strcasecmp($user->email, $validated['email']) !== 0) {
                    $this->checkPassword($user, $validated['current_password'] ?? null);
                    $user->email_verified_at = null;
                }
                $user->fill(collect($validated)->only(['name', 'email', 'phone'])->all())->save();

                return $user;
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['email' => 'Email sudah digunakan. Gunakan email lain.']);
        }

        return response()->json(['message' => 'Profil berhasil diperbarui.', 'user' => $user->only(['id', 'name', 'email', 'phone', 'role'])]);
    }

    public function password(Request $request)
    {
        abort_unless($request->user()->isPengguna(), 403);
        $validated = $request->validate([
            'current_password' => ['required', 'string', 'max:128'],
            'password' => ['required', 'string', 'min:8', 'max:128', 'confirmed', 'different:current_password'],
            'device_token' => ['nullable', 'string', 'max:4096'],
        ], ['password.different' => 'Kata sandi baru harus berbeda dari kata sandi saat ini.']);
        $accessToken = $request->user()->currentAccessToken();
        $currentTokenId = $accessToken instanceof PersonalAccessToken ? $accessToken->getKey() : null;

        DB::transaction(function () use ($request, $validated, $currentTokenId) {
            $user = User::query()->lockForUpdate()->findOrFail($request->user()->id);
            abort_unless($user->isPengguna() && $user->is_active, 403);
            $this->checkPassword($user, $validated['current_password']);
            $user->forceFill(['password' => $validated['password'], 'remember_token' => Str::random(60)])->save();
            $user->tokens()->when($currentTokenId !== null, fn ($query) => $query->whereKeyNot($currentTokenId))->delete();
            $deviceHash = empty($validated['device_token']) ? null : DeviceToken::hash($validated['device_token']);
            $user->deviceTokens()->when($deviceHash !== null, fn ($query) => $query->where('token_hash', '!=', $deviceHash))->delete();
        });

        return response()->json(['message' => 'Kata sandi berhasil diperbarui. Sesi di perangkat lain telah diakhiri.']);
    }

    private function checkPassword(User $user, ?string $password): void
    {
        if (! $password || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages(['current_password' => 'Kata sandi saat ini tidak sesuai.']);
        }
    }
}
