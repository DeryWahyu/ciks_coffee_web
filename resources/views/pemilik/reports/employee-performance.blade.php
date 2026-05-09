@extends('layouts.pemilik')

@section('title', 'Performa Karyawan')
@section('page-title', 'Pendapatan Per Karyawan')
@section('page-description', 'Laporan performa dan kontribusi pendapatan setiap karyawan')

@section('content')
    {{-- Month Filter --}}
    <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-5 mb-6">
        <form method="GET" action="{{ route('pemilik.performance.employees') }}" class="flex flex-col sm:flex-row items-end gap-4">
            <div class="flex-1 w-full sm:w-auto flex flex-col sm:flex-row gap-4">
                <div class="w-full sm:w-1/2">
                    <label class="block text-xs font-semibold text-caramel uppercase tracking-wider mb-1.5">Dari Tanggal</label>
                    <input type="date" name="start_date" value="{{ $startDate }}" class="w-full px-4 py-2.5 bg-cream-light border border-latte rounded-xl text-sm text-espresso focus:outline-none focus:ring-2 focus:ring-caramel/30 focus:border-caramel transition-all">
                </div>
                <div class="w-full sm:w-1/2">
                    <label class="block text-xs font-semibold text-caramel uppercase tracking-wider mb-1.5">Sampai Tanggal</label>
                    <input type="date" name="end_date" value="{{ $endDate }}" class="w-full px-4 py-2.5 bg-cream-light border border-latte rounded-xl text-sm text-espresso focus:outline-none focus:ring-2 focus:ring-caramel/30 focus:border-caramel transition-all">
                </div>
            </div>
            <button type="submit" class="w-full sm:w-auto px-6 py-2.5 bg-espresso text-cream font-semibold text-sm rounded-xl hover:bg-espresso-light transition-all duration-200 shadow-sm">Tampilkan</button>
        </form>
    </div>

    {{-- Summary --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-6">
        <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-5">
            <p class="text-[0.65rem] text-caramel font-semibold uppercase tracking-wider mb-2">Karyawan Aktif</p>
            <p class="text-2xl font-bold text-espresso">{{ $leaderboard->count() }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-5">
            <p class="text-[0.65rem] text-caramel font-semibold uppercase tracking-wider mb-2">Total Revenue</p>
            <p class="text-2xl font-bold text-espresso">Rp {{ number_format($grandTotal, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-5">
            <p class="text-[0.65rem] text-caramel font-semibold uppercase tracking-wider mb-2">Top Performer</p>
            <p class="text-2xl font-bold text-espresso truncate">{{ $leaderboard->first()?->name ?? '-' }}</p>
        </div>
    </div>

    {{-- Leaderboard --}}
    <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-6">
        <h4 class="text-sm font-bold text-espresso mb-5 flex items-center gap-2">
            <svg class="w-4.5 h-4.5 text-caramel" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 01-.982-3.172M9.497 14.25a7.454 7.454 0 00.981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 007.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M18.75 4.236c.982.143 1.954.317 2.916.52A6.003 6.003 0 0016.27 9.728M18.75 4.236V4.5c0 2.108-.966 3.99-2.48 5.228m0 0a6.01 6.01 0 01-2.27.793"/></svg>
            Leaderboard — {{ \Carbon\Carbon::parse($startDate)->translatedFormat('d M Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->translatedFormat('d M Y') }}
        </h4>

        @if($leaderboard->count() > 0)
            <div class="space-y-3">
                @foreach($leaderboard as $emp)
                    @php
                        $pct = $grandTotal > 0 ? ($emp->total_revenue / $grandTotal) * 100 : 0;
                    @endphp
                    <div class="p-4 rounded-xl border border-latte/30 hover:shadow-md transition-all {{ $emp->rank <= 3 ? 'bg-espresso/5 border-espresso/20' : 'bg-cream/20' }}">
                        <div class="flex items-center gap-4">
                            {{-- Numbered Rank --}}
                            <div class="w-10 h-10 rounded-xl {{ $emp->rank <= 3 ? 'bg-espresso text-cream' : 'bg-latte/50 text-espresso' }} flex items-center justify-center font-bold text-sm shrink-0 shadow-sm">
                                {{ $emp->rank }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between mb-1">
                                    <h5 class="text-sm font-bold text-espresso truncate">{{ $emp->name }}</h5>
                                    <span class="text-sm font-bold text-espresso whitespace-nowrap ml-3">Rp {{ number_format($emp->total_revenue, 0, ',', '.') }}</span>
                                </div>
                                <div class="w-full bg-latte/30 rounded-full h-2 mb-2">
                                    <div class="h-2 rounded-full bg-gradient-to-r {{ $emp->rank <= 3 ? 'from-espresso to-caramel-dark' : 'from-caramel to-caramel-light' }}" style="width: {{ min($pct, 100) }}%"></div>
                                </div>
                                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-caramel-dark">
                                    <span>{{ $emp->total_orders }} pesanan</span>
                                    <span>Avg Rp {{ number_format($emp->avg_order_value, 0, ',', '.') }}</span>
                                    <span class="font-medium text-espresso">{{ number_format($pct, 1) }}%</span>
                                    <a href="{{ route('pemilik.performance.employee-detail', ['user' => $emp->user_id, 'start_date' => $startDate, 'end_date' => $endDate]) }}" class="ml-auto text-xs font-semibold text-espresso hover:text-caramel-dark transition-colors flex items-center gap-1">
                                        Lihat Detail
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12 text-caramel-dark">
                <p class="text-sm">Belum ada data transaksi pada periode ini.</p>
            </div>
        @endif
    </div>
@endsection
