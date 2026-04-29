@extends('layouts.pemilik')

@section('title', 'Tambah Karyawan')
@section('page-title', 'Daftarkan Karyawan Baru')
@section('page-description', 'Buat akun karyawan baru untuk Ciks Coffee')

@section('content')
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-6 lg:p-8">
            <form method="POST" action="{{ route('pemilik.users.store') }}" id="create-user-form">
                @csrf

                {{-- Nama --}}
                <div class="mb-6">
                    <label for="name" class="block text-xs font-semibold text-espresso uppercase tracking-wider mb-2">
                        Nama Lengkap <span class="text-red-400">*</span>
                    </label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ old('name') }}"
                        placeholder="Masukkan nama lengkap"
                        class="w-full px-4 py-3 bg-cream-light border border-latte rounded-xl text-sm text-espresso placeholder-caramel-light focus:outline-none focus:border-espresso focus:ring-1 focus:ring-espresso/20 transition-all"
                        required
                    >
                    @error('name')
                        <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Email --}}
                <div class="mb-6">
                    <label for="email" class="block text-xs font-semibold text-espresso uppercase tracking-wider mb-2">
                        Email <span class="text-red-400">*</span>
                    </label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="nama@cikscoffee.com"
                        class="w-full px-4 py-3 bg-cream-light border border-latte rounded-xl text-sm text-espresso placeholder-caramel-light focus:outline-none focus:border-espresso focus:ring-1 focus:ring-espresso/20 transition-all"
                        required
                    >
                    @error('email')
                        <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Telepon --}}
                <div class="mb-6">
                    <label for="phone" class="block text-xs font-semibold text-espresso uppercase tracking-wider mb-2">
                        No. Telepon
                    </label>
                    <input
                        type="text"
                        id="phone"
                        name="phone"
                        value="{{ old('phone') }}"
                        placeholder="08xxxxxxxxxx"
                        class="w-full px-4 py-3 bg-cream-light border border-latte rounded-xl text-sm text-espresso placeholder-caramel-light focus:outline-none focus:border-espresso focus:ring-1 focus:ring-espresso/20 transition-all"
                    >
                    @error('phone')
                        <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Password --}}
                <div class="mb-6">
                    <label for="password" class="block text-xs font-semibold text-espresso uppercase tracking-wider mb-2">
                        Password <span class="text-red-400">*</span>
                    </label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Minimal 8 karakter"
                        class="w-full px-4 py-3 bg-cream-light border border-latte rounded-xl text-sm text-espresso placeholder-caramel-light focus:outline-none focus:border-espresso focus:ring-1 focus:ring-espresso/20 transition-all"
                        required
                    >
                    @error('password')
                        <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Konfirmasi Password --}}
                <div class="mb-8">
                    <label for="password_confirmation" class="block text-xs font-semibold text-espresso uppercase tracking-wider mb-2">
                        Konfirmasi Password <span class="text-red-400">*</span>
                    </label>
                    <input
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        placeholder="Ulangi password"
                        class="w-full px-4 py-3 bg-cream-light border border-latte rounded-xl text-sm text-espresso placeholder-caramel-light focus:outline-none focus:border-espresso focus:ring-1 focus:ring-espresso/20 transition-all"
                        required
                    >
                </div>

                {{-- Actions --}}
                <div class="flex items-center gap-3 pt-4 border-t border-latte/30">
                    <button type="submit" class="inline-flex items-center gap-2 bg-espresso hover:bg-espresso-light text-cream text-sm font-semibold px-6 py-3 rounded-xl transition-all duration-200 hover:shadow-md">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                        Daftarkan Karyawan
                    </button>
                    <a href="{{ route('pemilik.users.index') }}" class="text-sm text-caramel-dark hover:text-espresso font-medium px-4 py-3 rounded-xl hover:bg-latte/30 transition-colors">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
