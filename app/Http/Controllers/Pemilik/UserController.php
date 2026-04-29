<?php

namespace App\Http\Controllers\Pemilik;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index()
    {
        $users = User::whereIn('role', ['karyawan', 'pengguna'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('pemilik.users.index', compact('users'));
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
        $user->update(['is_active' => !$user->is_active]);

        $status = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->route('pemilik.users.index')
            ->with('success', "Akun {$user->name} berhasil {$status}.");
    }
}
