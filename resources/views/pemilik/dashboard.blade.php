@extends('layouts.pemilik')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-description', 'Ringkasan keseluruhan bisnis Ciks Coffee')

@section('content')
    {{-- Welcome Card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-6 mb-6">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 bg-espresso/10 rounded-2xl flex items-center justify-center shrink-0">
                <span class="text-2xl">👋</span>
            </div>
            <div>
                <h3 class="text-xl font-bold text-espresso">
                    Selamat Datang, {{ Auth::user()->name }}!
                </h3>
                <p class="text-sm text-caramel-dark mt-1">
                    Berikut ringkasan bisnis Ciks Coffee Anda hari ini.
                </p>
            </div>
        </div>
    </div>

    {{-- Quick Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
        {{-- Karyawan Aktif --}}
        <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-5 hover:shadow-md hover:border-caramel/30 transition-all duration-300">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 bg-espresso/10 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-espresso" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                    </svg>
                </div>
                <span class="text-[0.65rem] text-caramel font-semibold uppercase tracking-wider">Karyawan</span>
            </div>
            <p class="text-2xl font-bold text-espresso">{{ $totalKaryawan }}</p>
            <p class="text-xs text-caramel-dark mt-1">Karyawan aktif</p>
        </div>

        {{-- Produk --}}
        <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-5 hover:shadow-md hover:border-caramel/30 transition-all duration-300">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 bg-caramel/15 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-caramel-dark" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
                    </svg>
                </div>
                <span class="text-[0.65rem] text-caramel font-semibold uppercase tracking-wider">Produk</span>
            </div>
            <p class="text-2xl font-bold text-espresso">-</p>
            <p class="text-xs text-caramel-dark mt-1">Item menu tersedia</p>
        </div>

        {{-- Pendapatan Hari Ini --}}
        <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-5 hover:shadow-md hover:border-caramel/30 transition-all duration-300">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 bg-green-50 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"/>
                    </svg>
                </div>
                <span class="text-[0.65rem] text-caramel font-semibold uppercase tracking-wider">Pendapatan</span>
            </div>
            <p class="text-2xl font-bold text-espresso">Rp -</p>
            <p class="text-xs text-caramel-dark mt-1">Pendapatan hari ini</p>
        </div>


    </div>

    {{-- Quick Access Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-6 hover:shadow-md transition-all duration-300">
            <h4 class="text-sm font-bold text-espresso mb-4 flex items-center gap-2">
                <svg class="w-4.5 h-4.5 text-caramel" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/>
                </svg>
                Aksi Cepat
            </h4>
            <div class="space-y-2">
                <a href="{{ route('pemilik.users.create') }}" class="flex items-center gap-3 p-3 rounded-xl hover:bg-cream/50 transition-colors group">
                    <div class="w-8 h-8 bg-espresso/10 rounded-lg flex items-center justify-center group-hover:bg-espresso/20 transition-colors">
                        <svg class="w-4 h-4 text-espresso" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z"/>
                        </svg>
                    </div>
                    <span class="text-sm text-espresso/80 group-hover:text-espresso">Daftarkan Karyawan Baru</span>
                </a>
                <a href="{{ route('pemilik.products.index') }}" class="flex items-center gap-3 p-3 rounded-xl hover:bg-cream/50 transition-colors group">
                    <div class="w-8 h-8 bg-caramel/15 rounded-lg flex items-center justify-center group-hover:bg-caramel/25 transition-colors">
                        <svg class="w-4 h-4 text-caramel-dark" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                    </div>
                    <span class="text-sm text-espresso/80 group-hover:text-espresso">Tambah Produk Baru</span>
                </a>
                <a href="{{ route('pemilik.reports.sales') }}" class="flex items-center gap-3 p-3 rounded-xl hover:bg-cream/50 transition-colors group">
                    <div class="w-8 h-8 bg-green-50 rounded-lg flex items-center justify-center group-hover:bg-green-100 transition-colors">
                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                        </svg>
                    </div>
                    <span class="text-sm text-espresso/80 group-hover:text-espresso">Lihat Laporan Penjualan</span>
                </a>
            </div>
        </div>

        <div class="bg-espresso/5 border border-espresso/10 rounded-2xl p-6">
            <h4 class="text-sm font-bold text-espresso mb-3 flex items-center gap-2">
                <svg class="w-4.5 h-4.5 text-espresso" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd"/>
                </svg>
                Info Sistem
            </h4>
            <div class="space-y-3 text-sm text-caramel-dark">
                <p>Fitur-fitur berikut sedang dalam tahap pengembangan:</p>
                <ul class="space-y-1.5 ml-1">
                    <li class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 bg-caramel rounded-full"></span>
                        Manajemen produk & harga
                    </li>
                    <li class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 bg-caramel rounded-full"></span>
                        Manajemen bahan baku & stok
                    </li>

                    <li class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 bg-caramel rounded-full"></span>
                        Laporan & analisis bisnis
                    </li>
                    <li class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 bg-caramel rounded-full"></span>
                        Ekspor data (CSV/Excel/PDF)
                    </li>
                </ul>
            </div>
        </div>
    </div>
@endsection
