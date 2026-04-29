@extends('layouts.pemilik')

@section('title', 'Data Pengguna')
@section('page-title', 'Kelola Data Pengguna')
@section('page-description', 'Kelola akun karyawan dan pengguna')

@section('page-actions')
    <a href="{{ route('pemilik.users.create') }}" class="inline-flex items-center gap-2 bg-espresso hover:bg-espresso-light text-cream text-sm font-medium px-4 py-2.5 rounded-xl transition-all duration-200 hover:shadow-md" id="btn-add-user">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        Tambah Karyawan
    </a>
@endsection

@section('content')
    {{-- Mobile Card Layout --}}
    <div class="md:hidden space-y-4">
        @forelse ($users as $user)
            <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-5">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-espresso/10 rounded-full flex items-center justify-center">
                            <span class="text-sm font-semibold text-espresso">{{ substr($user->name, 0, 1) }}</span>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-espresso">{{ $user->name }}</p>
                            <p class="text-xs text-caramel-dark">{{ $user->email }}</p>
                        </div>
                    </div>
                    <span class="inline-flex items-center gap-1.5 text-xs font-medium {{ $user->is_active ? 'text-green-600' : 'text-red-500' }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $user->is_active ? 'bg-green-500' : 'bg-red-400' }}"></span>
                        {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </div>
                <div class="flex items-center gap-3 text-xs text-caramel-dark mb-4">
                    <div class="flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-caramel" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/>
                        </svg>
                        {{ $user->phone ?? '-' }}
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold
                        {{ $user->role === 'karyawan' ? 'bg-caramel/15 text-espresso' : 'bg-blue-50 text-blue-700' }}">
                        {{ ucfirst($user->role) }}
                    </span>
                </div>
                <div class="border-t border-latte/30 pt-3">
                    <form method="POST" action="{{ route('pemilik.users.toggle-status', $user) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="w-full text-xs font-semibold px-4 py-2.5 rounded-xl transition-colors {{ $user->is_active ? 'text-red-600 bg-red-50 hover:bg-red-100' : 'text-green-600 bg-green-50 hover:bg-green-100' }}">
                            {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-8">
                <div class="flex flex-col items-center">
                    <div class="w-14 h-14 bg-latte/30 rounded-2xl flex items-center justify-center mb-3">
                        <svg class="w-7 h-7 text-caramel" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                        </svg>
                    </div>
                    <p class="text-sm text-caramel-dark font-medium">Belum ada pengguna terdaftar</p>
                    <a href="{{ route('pemilik.users.create') }}" class="text-sm text-espresso font-semibold mt-2 hover:underline">+ Tambah Karyawan Baru</a>
                </div>
            </div>
        @endforelse

        @if ($users->hasPages())
            <div class="px-2 py-4">
                {{ $users->links() }}
            </div>
        @endif
    </div>

    {{-- Desktop Table Layout --}}
    <div class="hidden md:block bg-white rounded-2xl shadow-sm border border-latte/50 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full" id="users-table">
                <thead>
                    <tr class="border-b border-latte/40">
                        <th class="text-left px-6 py-4 text-xs font-bold text-espresso uppercase tracking-wider">Nama</th>
                        <th class="text-left px-6 py-4 text-xs font-bold text-espresso uppercase tracking-wider">Email</th>
                        <th class="text-left px-6 py-4 text-xs font-bold text-espresso uppercase tracking-wider hidden lg:table-cell">Telepon</th>
                        <th class="text-left px-6 py-4 text-xs font-bold text-espresso uppercase tracking-wider">Role</th>
                        <th class="text-left px-6 py-4 text-xs font-bold text-espresso uppercase tracking-wider">Status</th>
                        <th class="text-center px-6 py-4 text-xs font-bold text-espresso uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-latte/30">
                    @forelse ($users as $user)
                        <tr class="hover:bg-cream/30 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-espresso/10 rounded-full flex items-center justify-center">
                                        <span class="text-xs font-semibold text-espresso">{{ substr($user->name, 0, 1) }}</span>
                                    </div>
                                    <span class="text-sm font-medium text-espresso">{{ $user->name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-caramel-dark">{{ $user->email }}</td>
                            <td class="px-6 py-4 text-sm text-caramel-dark hidden lg:table-cell">{{ $user->phone ?? '-' }}</td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold
                                    {{ $user->role === 'karyawan' ? 'bg-caramel/15 text-espresso' : 'bg-blue-50 text-blue-700' }}">
                                    {{ ucfirst($user->role) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center gap-1.5 text-xs font-medium {{ $user->is_active ? 'text-green-600' : 'text-red-500' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $user->is_active ? 'bg-green-500' : 'bg-red-400' }}"></span>
                                    {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <form method="POST" action="{{ route('pemilik.users.toggle-status', $user) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="text-xs font-medium px-3 py-1.5 rounded-lg transition-colors {{ $user->is_active ? 'text-red-600 hover:bg-red-50' : 'text-green-600 hover:bg-green-50' }}">
                                        {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="w-14 h-14 bg-latte/30 rounded-2xl flex items-center justify-center mb-3">
                                        <svg class="w-7 h-7 text-caramel" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                                        </svg>
                                    </div>
                                    <p class="text-sm text-caramel-dark font-medium">Belum ada pengguna terdaftar</p>
                                    <a href="{{ route('pemilik.users.create') }}" class="text-sm text-espresso font-semibold mt-2 hover:underline">+ Tambah Karyawan Baru</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="px-6 py-4 border-t border-latte/30">
                {{ $users->links() }}
            </div>
        @endif
    </div>
@endsection
