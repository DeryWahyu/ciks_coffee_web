@extends('layouts.karyawan')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard Karyawan')
@section('page-description', 'Selamat bekerja di Ciks Coffee!')

@section('content')
    {{-- Welcome Card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-6 mb-6">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 bg-caramel/15 rounded-2xl flex items-center justify-center shrink-0">
                <span class="text-2xl">☕</span>
            </div>
            <div>
                <h3 class="text-xl font-bold text-espresso" style="font-family: 'Playfair Display', serif;">
                    Halo, {{ Auth::user()->name }}!
                </h3>
                <p class="text-sm text-caramel-dark mt-1">
                    Selamat bekerja. Berikut ringkasan aktivitas Anda hari ini.
                </p>
            </div>
        </div>
    </div>

    {{-- Quick Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-6">
        <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-5 hover:shadow-md transition-all duration-300">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 bg-espresso/10 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-espresso" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z"/>
                    </svg>
                </div>
                <span class="text-[0.65rem] text-caramel font-semibold uppercase tracking-wider">Pesanan</span>
            </div>
            <p class="text-2xl font-bold text-espresso">-</p>
            <p class="text-xs text-caramel-dark mt-1">Pesanan hari ini</p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-5 hover:shadow-md transition-all duration-300">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 bg-caramel/15 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-caramel-dark" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-[0.65rem] text-caramel font-semibold uppercase tracking-wider">Pending</span>
            </div>
            <p class="text-2xl font-bold text-espresso">-</p>
            <p class="text-xs text-caramel-dark mt-1">Menunggu proses</p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-5 hover:shadow-md transition-all duration-300">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 bg-green-50 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-[0.65rem] text-caramel font-semibold uppercase tracking-wider">Selesai</span>
            </div>
            <p class="text-2xl font-bold text-espresso">-</p>
            <p class="text-xs text-caramel-dark mt-1">Pesanan selesai</p>
        </div>
    </div>

    {{-- Info Card --}}
    <div class="bg-espresso/5 border border-espresso/10 rounded-2xl p-6">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-espresso mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd"/>
            </svg>
            <div>
                <h3 class="text-sm font-semibold text-espresso">Fitur Kasir Segera Hadir</h3>
                <p class="text-sm text-caramel-dark mt-1">
                    Fitur kasir dan manajemen pesanan sedang dalam pengembangan. Saat ini Anda dapat menggunakan dashboard ini untuk melihat ringkasan aktivitas.
                </p>
            </div>
        </div>
    </div>
@endsection
