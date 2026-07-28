<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->role === User::ROLE_PENGGUNA, 403);

        $validated = $request->validate([
            'token' => ['required', 'string', 'max:4096'],
            'platform' => ['required', 'in:android,ios'],
        ]);

        $deviceToken = DeviceToken::query()->updateOrCreate(
            ['token_hash' => DeviceToken::hash($validated['token'])],
            [
                'user_id' => $request->user()->id,
                'token' => $validated['token'],
                'platform' => $validated['platform'],
                'last_seen_at' => now(),
            ],
        );

        return response()->json([
            'message' => 'Perangkat berhasil didaftarkan untuk notifikasi.',
        ], $deviceToken->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(Request $request): JsonResponse
    {
        abort_unless($request->user()?->role === User::ROLE_PENGGUNA, 403);

        $validated = $request->validate([
            'token' => ['required', 'string', 'max:4096'],
        ]);

        DeviceToken::query()
            ->where('user_id', $request->user()->id)
            ->where('token_hash', DeviceToken::hash($validated['token']))
            ->delete();

        return response()->json([
            'message' => 'Perangkat berhenti menerima notifikasi.',
        ]);
    }
}
