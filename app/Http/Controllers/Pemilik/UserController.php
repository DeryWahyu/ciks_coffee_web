<?php

namespace App\Http\Controllers\Pemilik;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'karyawan');

        $query = User::orderBy('created_at', 'desc');

        if ($tab === 'pengguna') {
            $query->where('role', 'pengguna');
        } else {
            $query->where('role', 'karyawan');
        }

        $users = $query->paginate(10)->appends(['tab' => $tab]);

        return view('pemilik.users.index', compact('users', 'tab'));
    }

    /**
     * Show the form for creating a new karyawan.
     */
    public function create()
    {
        return view('pemilik.users.create');
    }

    /**
     * Store a newly created karyawan.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.unique' => 'Email sudah terdaftar.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => $validated['password'],
            'role' => 'karyawan',
            'is_active' => true,
        ]);

        return redirect()->route('pemilik.users.index')
            ->with('success', 'Karyawan baru berhasil didaftarkan.');
    }

    /**
     * Toggle user active status.
     */
    public function toggleStatus(User $user)
    {
        $changed = DB::transaction(function () use ($user) {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            if ($lockedUser->is_active && $lockedUser->isKaryawan()
                && Order::activeQueue()->where('cashier_id', $lockedUser->id)->exists()) {
                return false;
            }
            $lockedUser->update(['is_active' => ! $lockedUser->is_active]);

            if (! $lockedUser->is_active) {
                $lockedUser->tokens()->delete();
            }

            $user->setRawAttributes($lockedUser->getAttributes(), true);

            return true;
        });

        if (! $changed) {
            return back()->with('error', 'Karyawan masih menangani pesanan aktif. Selesaikan pesanan sebelum menonaktifkan akun.');
        }

        $status = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->route('pemilik.users.index', ['tab' => $user->role])
            ->with('success', "Akun {$user->name} berhasil {$status}.");
    }

    /**
     * Reset user password to custom.
     */
    public function resetPassword(Request $request, User $user)
    {
        $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        DB::transaction(function () use ($user, $request) {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $lockedUser->password = $request->password;
            $lockedUser->setRememberToken(Str::random(60));
            $lockedUser->save();
            if (config('session.driver') === 'database') {
                DB::connection(config('session.connection'))->table(config('session.table'))->where('user_id', $lockedUser->id)->delete();
            }
            $lockedUser->tokens()->delete();
        }, 3);

        return redirect()->route('pemilik.users.index', ['tab' => $user->role])
            ->with('success', "Password untuk akun {$user->name} berhasil diubah.");
    }
}
