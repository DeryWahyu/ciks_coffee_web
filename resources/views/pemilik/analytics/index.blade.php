@extends('layouts.pemilik')
@section('title', 'Analisis Bisnis')
@section('page-title', 'Analisis Bisnis')
@section('page-description', 'Tren penjualan, analisis produk, jam sibuk, dan KPI')

@section('content')
{{-- Period Filter --}}
<div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-4 mb-6">
    <div class="flex flex-wrap items-center gap-2">
        <span class="text-xs font-semibold text-caramel uppercase tracking-wider mr-2">Periode:</span>
        @foreach(['7' => '7 Hari', '30' => '30 Hari', '90' => '90 Hari'] as $val => $label)
            <a href="{{ route('pemilik.analytics.index', ['period' => $val]) }}" class="px-4 py-2 text-xs font-semibold rounded-xl border transition-all {{ $period == $val ? 'bg-espresso text-cream border-espresso' : 'bg-white text-caramel border-latte/50 hover:border-caramel' }}">{{ $label }}</a>
        @endforeach
    </div>
</div>

{{-- KPI Cards --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @php
        $kpiCards = [
            ['label' => 'Total Pendapatan', 'value' => 'Rp ' . number_format($kpi['revenue']['current'], 0, ',', '.'), 'change' => $kpi['revenue']['change'], 'icon' => 'M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z'],
            ['label' => 'Total Pesanan', 'value' => number_format($kpi['orders']['current']), 'change' => $kpi['orders']['change'], 'icon' => 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z'],
            ['label' => 'Rata-rata Order', 'value' => 'Rp ' . number_format($kpi['avg_order']['current'], 0, ',', '.'), 'change' => $kpi['avg_order']['change'], 'icon' => 'M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['label' => 'Pelanggan Unik', 'value' => number_format($kpi['customers']['current']), 'change' => $kpi['customers']['change'], 'icon' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z'],
        ];
    @endphp
    @foreach($kpiCards as $card)
    <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-4 sm:p-5 hover:shadow-md transition-all">
        <div class="flex items-center justify-between mb-2">
            <div class="w-9 h-9 bg-espresso/10 rounded-lg flex items-center justify-center">
                <svg class="w-4.5 h-4.5 text-espresso" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $card['icon'] }}"/></svg>
            </div>
            @php $chg = $card['change']; @endphp
            <span class="text-xs font-bold px-2 py-0.5 rounded-full {{ $chg >= 0 ? 'bg-green-50 text-green-600' : 'bg-red-50 text-red-600' }}">
                {{ $chg >= 0 ? '+' : '' }}{{ number_format($chg, 1) }}%
            </span>
        </div>
        <p class="text-lg sm:text-xl font-bold text-espresso truncate">{{ $card['value'] }}</p>
        <p class="text-[0.6rem] text-caramel font-semibold uppercase tracking-wider mt-1">{{ $card['label'] }}</p>
    </div>
    @endforeach
</div>

{{-- Tren Penjualan Chart --}}
<div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-5 mb-6">
    <h4 class="text-sm font-bold text-espresso mb-4">Tren Penjualan — {{ $days }} Hari Terakhir</h4>
    <div class="relative" style="height: 280px;">
        <canvas id="salesTrendChart"></canvas>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-6">
    {{-- Jam Sibuk --}}
    <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-5">
        <h4 class="text-sm font-bold text-espresso mb-4">Jam Sibuk</h4>
        <div class="relative" style="height: 220px;">
            <canvas id="peakHoursChart"></canvas>
        </div>
        @php $peakHour = $hourlyData->sortByDesc('count')->first(); @endphp
        @if($peakHour && $peakHour['count'] > 0)
        <p class="text-xs text-caramel-dark mt-3 text-center">Jam tersibuk: <span class="font-bold text-espresso">{{ $peakHour['hour'] }}</span> ({{ $peakHour['count'] }} pesanan)</p>
        @endif
    </div>

    {{-- Metode Pembayaran --}}
    <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-5">
        <h4 class="text-sm font-bold text-espresso mb-4">Distribusi Pembayaran</h4>
        @if($paymentMethods->count() > 0)
        <div class="flex items-center justify-center" style="height: 200px;">
            <canvas id="paymentChart"></canvas>
        </div>
        <div class="mt-4 space-y-2">
            @foreach($paymentMethods as $pm)
            <div class="flex items-center justify-between text-sm">
                <span class="text-caramel-dark">{{ strtoupper($pm->payment_method) }}</span>
                <div class="text-right">
                    <span class="font-bold text-espresso">{{ $pm->count }} pesanan</span>
                    <span class="text-xs text-caramel-dark ml-2">(Rp {{ number_format($pm->total, 0, ',', '.') }})</span>
                </div>
            </div>
            @endforeach
        </div>
        @else
            <p class="text-sm text-caramel-dark text-center py-12">Belum ada data</p>
        @endif
    </div>
</div>

{{-- Produk Terlaris --}}
<div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-5 mb-6">
    <h4 class="text-sm font-bold text-espresso mb-4">Top 10 Produk Terlaris</h4>
    @if($topProducts->count() > 0)
        {{-- Mobile cards --}}
        <div class="sm:hidden space-y-3">
            @foreach($topProducts as $i => $tp)
            <div class="p-3 rounded-xl border border-latte/20 bg-cream/20">
                <div class="flex items-center gap-3">
                    <span class="w-7 h-7 rounded-lg {{ $i < 3 ? 'bg-espresso text-cream' : 'bg-latte/50 text-espresso' }} flex items-center justify-center text-xs font-bold shrink-0">{{ $i + 1 }}</span>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-espresso truncate">{{ $tp->product_name }} @if($tp->variant)<span class="text-xs text-caramel">({{ ucfirst($tp->variant) }})</span>@endif</p>
                        <p class="text-xs text-caramel-dark">{{ $tp->total_qty }} item · Rp {{ number_format($tp->total_revenue, 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        {{-- Desktop table --}}
        <div class="hidden sm:block overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="border-b border-latte/40">
                    <th class="text-left py-3 px-3 text-xs font-semibold text-caramel uppercase tracking-wider">#</th>
                    <th class="text-left py-3 px-3 text-xs font-semibold text-caramel uppercase tracking-wider">Produk</th>
                    <th class="text-left py-3 px-3 text-xs font-semibold text-caramel uppercase tracking-wider">Varian</th>
                    <th class="text-right py-3 px-3 text-xs font-semibold text-caramel uppercase tracking-wider">Jml</th>
                    <th class="text-right py-3 px-3 text-xs font-semibold text-caramel uppercase tracking-wider">Pendapatan</th>
                </tr></thead>
                <tbody class="divide-y divide-latte/20">
                    @foreach($topProducts as $i => $tp)
                    <tr class="hover:bg-cream/30 transition-colors">
                        <td class="py-3 px-3"><span class="w-6 h-6 rounded {{ $i < 3 ? 'bg-espresso text-cream' : 'bg-latte/40 text-espresso' }} inline-flex items-center justify-center text-xs font-bold">{{ $i+1 }}</span></td>
                        <td class="py-3 px-3 font-medium text-espresso">{{ $tp->product_name }}</td>
                        <td class="py-3 px-3 text-caramel-dark">{{ $tp->variant ? ucfirst($tp->variant) : '-' }}</td>
                        <td class="py-3 px-3 text-right font-semibold text-espresso">{{ $tp->total_qty }}</td>
                        <td class="py-3 px-3 text-right font-semibold text-espresso">Rp {{ number_format($tp->total_revenue, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="text-sm text-caramel-dark text-center py-10">Belum ada data produk pada periode ini.</p>
    @endif
</div>

{{-- Ringkasan Harian --}}
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-5">
        <h4 class="text-sm font-bold text-espresso mb-2">Rata-rata Pendapatan / Hari</h4>
        <p class="text-2xl font-bold text-espresso">Rp {{ number_format($dailyAvgRevenue, 0, ',', '.') }}</p>
        <p class="text-xs text-caramel-dark mt-1">Berdasarkan {{ $days }} hari terakhir</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-5">
        <h4 class="text-sm font-bold text-espresso mb-2">Rata-rata Pesanan / Hari</h4>
        <p class="text-2xl font-bold text-espresso">{{ number_format($dailyAvgOrders, 1) }}</p>
        <p class="text-xs text-caramel-dark mt-1">Berdasarkan {{ $days }} hari terakhir</p>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
const chartFont = { family: "'Inter', sans-serif" };
Chart.defaults.font.family = chartFont.family;

// Sales Trend
new Chart(document.getElementById('salesTrendChart'), {
    type: 'line',
    data: {
        labels: {!! json_encode($salesTrend->pluck('date')) !!},
        datasets: [
            { label: 'Pendapatan', data: {!! json_encode($salesTrend->pluck('revenue')) !!}, borderColor: '#3E2723', backgroundColor: 'rgba(62,39,35,0.08)', fill: true, tension: 0.35, borderWidth: 2, pointRadius: 3, pointBackgroundColor: '#3E2723', yAxisID: 'y' },
            { label: 'Pesanan', data: {!! json_encode($salesTrend->pluck('orders')) !!}, borderColor: '#A1887F', backgroundColor: 'rgba(161,136,127,0.1)', fill: false, tension: 0.35, borderWidth: 2, borderDash: [4,4], pointRadius: 2, yAxisID: 'y1' }
        ]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: { legend: { position: 'top', labels: { padding: 16, font: { size: 11 } } }, tooltip: { callbacks: { label: ctx => ctx.dataset.label === 'Pendapatan' ? 'Rp ' + ctx.raw.toLocaleString('id-ID') : ctx.raw + ' pesanan' } } },
        scales: {
            y: { type: 'linear', position: 'left', beginAtZero: true, ticks: { callback: v => 'Rp ' + (v/1000) + 'k', font: { size: 10 } }, grid: { color: 'rgba(161,136,127,0.1)' } },
            y1: { type: 'linear', position: 'right', beginAtZero: true, grid: { drawOnChartArea: false }, ticks: { font: { size: 10 } } },
            x: { grid: { display: false }, ticks: { font: { size: 10 }, maxRotation: 45 } }
        }
    }
});

// Peak Hours
new Chart(document.getElementById('peakHoursChart'), {
    type: 'bar',
    data: {
        labels: {!! json_encode($hourlyData->pluck('hour')) !!},
        datasets: [{ label: 'Pesanan', data: {!! json_encode($hourlyData->pluck('count')) !!}, backgroundColor: ({raw, dataIndex}) => raw === Math.max(...{!! json_encode($hourlyData->pluck('count')) !!}) ? '#3E2723' : 'rgba(161,136,127,0.4)', borderRadius: 4 }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, grid: { color: 'rgba(161,136,127,0.1)' }, ticks: { font: { size: 10 } } }, x: { grid: { display: false }, ticks: { font: { size: 9 }, maxRotation: 90 } } }
    }
});

// Payment
@if($paymentMethods->count() > 0)
new Chart(document.getElementById('paymentChart'), {
    type: 'doughnut',
    data: {
        labels: {!! json_encode($paymentMethods->pluck('payment_method')->map(fn($m)=>strtoupper($m))) !!},
        datasets: [{ data: {!! json_encode($paymentMethods->pluck('count')) !!}, backgroundColor: ['#3E2723','#A1887F','#D7CCC8','#F5F5DC'], borderWidth: 0 }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { padding: 12, font: { size: 11 } } } } }
});
@endif
</script>
@endpush
