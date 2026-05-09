@extends('layouts.pemilik')
@section('title', 'Dasbor')
@section('page-title', 'Dasbor')
@section('page-description', 'Ringkasan keseluruhan bisnis Ciks Coffee')

@section('content')
{{-- Welcome --}}
<div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-6 mb-6">
    <div class="flex items-start gap-4">
        <div>
            <h3 class="text-xl font-bold text-espresso">Selamat Datang, {{ Auth::user()->name }}!</h3>
            <p class="text-sm text-caramel-dark mt-1">Berikut ringkasan bisnis Ciks Coffee Anda hari ini, {{ now()->translatedFormat('l, d F Y') }}.</p>
        </div>
    </div>
</div>

{{-- Stats Cards --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-4 sm:p-5 hover:shadow-md transition-all">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 bg-green-50 rounded-xl flex items-center justify-center">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"/></svg>
            </div>
        </div>
        <p class="text-xl sm:text-2xl font-bold text-espresso">Rp {{ number_format($todayRevenue, 0, ',', '.') }}</p>
        <p class="text-xs text-caramel-dark mt-1">Pendapatan hari ini</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-4 sm:p-5 hover:shadow-md transition-all">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 bg-espresso/10 rounded-xl flex items-center justify-center">
                <svg class="w-5 h-5 text-espresso" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z"/></svg>
            </div>
        </div>
        <p class="text-xl sm:text-2xl font-bold text-espresso">{{ $todayOrders }}</p>
        <p class="text-xs text-caramel-dark mt-1">Pesanan hari ini</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-4 sm:p-5 hover:shadow-md transition-all">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 bg-amber-50 rounded-xl flex items-center justify-center">
                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>
        <p class="text-xl sm:text-2xl font-bold text-espresso">{{ $pendingOrders }}</p>
        <p class="text-xs text-caramel-dark mt-1">Pesanan pending</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-4 sm:p-5 hover:shadow-md transition-all">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9"/></svg>
            </div>
        </div>
        <p class="text-xl sm:text-2xl font-bold text-espresso">Rp {{ number_format($monthRevenue, 0, ',', '.') }}</p>
        <p class="text-xs text-caramel-dark mt-1">Pendapatan bulan ini ({{ $monthOrders }} pesanan)</p>
    </div>
</div>

{{-- Chart + Top Products --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-6">
    {{-- Revenue Chart --}}
    <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-latte/50 p-5">
        <h4 class="text-sm font-bold text-espresso mb-4">Pendapatan 7 Hari Terakhir</h4>
        <canvas id="revenueChart" height="200"></canvas>
    </div>
    {{-- Top Products --}}
    <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-5">
        <h4 class="text-sm font-bold text-espresso mb-4">Produk Terlaris Bulan Ini</h4>
        @if($topProducts->count() > 0)
            <div class="space-y-3">
                @foreach($topProducts as $i => $tp)
                @php $maxQ = $topProducts->first()->total_qty; $pct = $maxQ > 0 ? ($tp->total_qty / $maxQ) * 100 : 0; @endphp
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs font-semibold text-espresso truncate mr-2">{{ $tp->product_name }}</span>
                        <span class="text-xs font-bold text-espresso whitespace-nowrap">{{ $tp->total_qty }}</span>
                    </div>
                    <div class="w-full bg-latte/30 rounded-full h-2">
                        <div class="h-2 rounded-full bg-gradient-to-r from-espresso to-caramel" style="width:{{ $pct }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-caramel-dark text-center py-8">Belum ada data</p>
        @endif
    </div>
</div>

{{-- Recent Orders + Quick Stats --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    {{-- Recent Orders --}}
    <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-latte/50 p-5">
        <h4 class="text-sm font-bold text-espresso mb-4">Pesanan Terbaru</h4>
        @if($recentOrders->count() > 0)
        <div class="space-y-3">
            @foreach($recentOrders as $order)
            <div class="flex items-center justify-between p-3 rounded-xl bg-cream/30 border border-latte/20">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-espresso">{{ $order->order_number }}</p>
                    <p class="text-xs text-caramel-dark">{{ $order->customer_name }} · {{ $order->created_at->diffForHumans() }}</p>
                </div>
                <div class="text-right shrink-0 ml-3">
                    <p class="text-sm font-bold text-espresso">{{ $order->formatted_total }}</p>
                    <span class="text-[0.6rem] font-semibold px-2 py-0.5 rounded-full {{ $order->status_color }}">{{ $order->status_label }}</span>
                </div>
            </div>
            @endforeach
        </div>
        @else
            <p class="text-sm text-caramel-dark text-center py-8">Belum ada pesanan</p>
        @endif
    </div>

    {{-- Quick Info --}}
    <div class="space-y-5">
        <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-5">
            <h4 class="text-sm font-bold text-espresso mb-3">Info Ringkas</h4>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between"><span class="text-caramel-dark">Karyawan Aktif</span><span class="font-bold text-espresso">{{ $totalKaryawan }}</span></div>
                <div class="flex justify-between"><span class="text-caramel-dark">Menu Tersedia</span><span class="font-bold text-espresso">{{ $totalProducts }}</span></div>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-5">
            <h4 class="text-sm font-bold text-espresso mb-3">Metode Pembayaran</h4>
            @if($paymentStats->count() > 0)
                <canvas id="paymentChart" height="160"></canvas>
            @else
                <p class="text-sm text-caramel-dark text-center py-4">Belum ada data</p>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
// Revenue Chart
const rCtx = document.getElementById('revenueChart');
if (rCtx) {
    new Chart(rCtx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($last7Days->pluck('day')) !!},
            datasets: [{
                label: 'Pendapatan',
                data: {!! json_encode($last7Days->pluck('revenue')) !!},
                backgroundColor: 'rgba(62,39,35,0.15)',
                borderColor: '#3E2723',
                borderWidth: 2,
                borderRadius: 8,
                hoverBackgroundColor: 'rgba(62,39,35,0.3)',
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false },
                tooltip: { callbacks: { label: ctx => 'Rp ' + ctx.raw.toLocaleString('id-ID') } }
            },
            scales: {
                y: { beginAtZero: true, ticks: { callback: v => 'Rp ' + (v/1000) + 'k' }, grid: { color: 'rgba(161,136,127,0.1)' } },
                x: { grid: { display: false } }
            }
        }
    });
}
// Payment Chart
@if($paymentStats->count() > 0)
const pCtx = document.getElementById('paymentChart');
if (pCtx) {
    new Chart(pCtx, {
        type: 'doughnut',
        data: {
            labels: {!! json_encode($paymentStats->pluck('payment_method')->map(fn($m) => strtoupper($m))) !!},
            datasets: [{ data: {!! json_encode($paymentStats->pluck('count')) !!}, backgroundColor: ['#3E2723','#A1887F','#D7CCC8'], borderWidth: 0 }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { padding: 12, font: { size: 11 } } } } }
    });
}
@endif
</script>
@endpush
