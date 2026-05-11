@extends('layouts.karyawan')
@section('title', 'Pendapatan Karyawan')
@section('page-title', 'Pendapatan Saya')
@section('page-description', 'Rincian penghasilan dari transaksi yang Anda tangani')

@section('content')
{{-- Stats Cards --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4 mb-6">
    <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-3 sm:p-4">
        <div class="w-8 h-8 bg-espresso/10 rounded-lg flex items-center justify-center mb-2">
            <svg class="w-4 h-4 text-espresso" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        </div>
        <p class="text-[0.6rem] sm:text-[0.65rem] text-caramel font-semibold uppercase tracking-wider mb-1">Total Pendapatan</p>
        <p class="text-lg sm:text-xl font-bold text-espresso">Rp {{ number_format($stats['total_revenue'], 0, ',', '.') }}</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-3 sm:p-4">
        <div class="w-8 h-8 bg-caramel/15 rounded-lg flex items-center justify-center mb-2">
            <svg class="w-4 h-4 text-caramel-dark" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
        </div>
        <p class="text-[0.6rem] sm:text-[0.65rem] text-caramel font-semibold uppercase tracking-wider mb-1">Total Transaksi</p>
        <p class="text-lg sm:text-xl font-bold text-espresso">{{ number_format($stats['total_transactions']) }}</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-3 sm:p-4">
        <div class="w-8 h-8 bg-latte/50 rounded-lg flex items-center justify-center mb-2">
            <svg class="w-4 h-4 text-espresso" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" /></svg>
        </div>
        <p class="text-[0.6rem] sm:text-[0.65rem] text-caramel font-semibold uppercase tracking-wider mb-1">Rata-rata / Transaksi</p>
        <p class="text-lg sm:text-xl font-bold text-espresso">Rp {{ number_format($stats['avg_transaction'], 0, ',', '.') }}</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-3 sm:p-4">
        <div class="w-8 h-8 bg-green-50 rounded-lg flex items-center justify-center mb-2">
            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        </div>
        <p class="text-[0.6rem] sm:text-[0.65rem] text-caramel font-semibold uppercase tracking-wider mb-1">Pendapatan Hari Ini</p>
        <p class="text-lg sm:text-xl font-bold text-espresso">Rp {{ number_format($stats['today_revenue'], 0, ',', '.') }}</p>
    </div>
</div>

{{-- Filters --}}
<div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-4 sm:p-5 mb-6">
    <form method="GET" action="{{ route('karyawan.income.index') }}">
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1">
                <label class="block text-xs font-semibold text-espresso mb-1">Dari Tanggal</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full px-3 py-2 text-sm border border-latte/60 rounded-xl focus:ring-2 focus:ring-caramel/30 focus:border-caramel outline-none">
            </div>
            <div class="flex-1">
                <label class="block text-xs font-semibold text-espresso mb-1">Sampai Tanggal</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full px-3 py-2 text-sm border border-latte/60 rounded-xl focus:ring-2 focus:ring-caramel/30 focus:border-caramel outline-none">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="px-5 py-2 text-sm font-semibold bg-espresso text-cream rounded-xl hover:bg-espresso-light transition">Terapkan</button>
                <a href="{{ route('karyawan.income.index') }}" class="px-5 py-2 text-sm font-medium text-caramel hover:text-espresso border border-latte/60 rounded-xl transition text-center">Reset</a>
            </div>
        </div>
    </form>
</div>

{{-- Income List --}}
<div class="bg-white rounded-2xl shadow-sm border border-latte/50 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-latte/50 bg-latte/10">
                    <th class="py-4 px-5 text-xs font-bold text-espresso uppercase tracking-wider">Tanggal</th>
                    <th class="py-4 px-5 text-xs font-bold text-espresso uppercase tracking-wider text-center">Total Transaksi</th>
                    <th class="py-4 px-5 text-xs font-bold text-espresso uppercase tracking-wider text-right">Pendapatan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-latte/30">
                @forelse ($incomeByDate as $income)
                <tr class="hover:bg-latte/5 transition-colors">
                    <td class="py-4 px-5 text-sm font-semibold text-espresso">
                        {{ \Carbon\Carbon::parse($income->date)->translatedFormat('l, d F Y') }}
                    </td>
                    <td class="py-4 px-5 text-sm text-caramel-dark text-center">
                        {{ $income->transactions }} Pesanan
                    </td>
                    <td class="py-4 px-5 text-sm font-bold text-espresso text-right">
                        Rp {{ number_format($income->revenue, 0, ',', '.') }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="py-12 text-center">
                        <div class="w-14 h-14 bg-latte/30 rounded-2xl flex items-center justify-center mb-3 mx-auto">
                            <svg class="w-7 h-7 text-caramel" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <p class="text-sm text-caramel-dark font-medium">Belum ada pendapatan</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if ($incomeByDate->hasPages())
    <div class="mt-6">{{ $incomeByDate->links() }}</div>
@endif
@endsection
