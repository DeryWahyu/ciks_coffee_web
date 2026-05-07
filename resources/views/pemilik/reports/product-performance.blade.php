@extends('layouts.pemilik')

@section('title', 'Performa Produk')
@section('page-title', 'Pendapatan Per Produk')
@section('page-description', 'Laporan produk terlaris dan kontribusi pendapatan')

@section('content')
    {{-- Month Filter --}}
    <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-5 mb-6">
        <form method="GET" action="{{ route('pemilik.performance.products') }}" class="flex flex-col sm:flex-row items-end gap-4">
            <div class="flex-1 w-full sm:w-auto">
                <label class="block text-xs font-semibold text-caramel uppercase tracking-wider mb-1.5">Periode</label>
                <input type="month" name="month" value="{{ $month }}" class="w-full px-4 py-2.5 bg-cream-light border border-latte rounded-xl text-sm text-espresso focus:outline-none focus:ring-2 focus:ring-caramel/30 focus:border-caramel transition-all">
            </div>
            <button type="submit" class="w-full sm:w-auto px-6 py-2.5 bg-espresso text-cream font-semibold text-sm rounded-xl hover:bg-espresso-light transition-all duration-200 shadow-sm">Tampilkan</button>
        </form>
    </div>

    {{-- Summary --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-5 mb-6">
        <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-4 sm:p-5">
            <p class="text-[0.65rem] text-caramel font-semibold uppercase tracking-wider mb-2">Total Terjual</p>
            <p class="text-xl sm:text-2xl font-bold text-espresso">{{ number_format($totalProductsSold) }} <span class="text-sm font-normal text-caramel-dark">item</span></p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-4 sm:p-5">
            <p class="text-[0.65rem] text-caramel font-semibold uppercase tracking-wider mb-2">Total Pendapatan</p>
            <p class="text-xl sm:text-2xl font-bold text-espresso">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-4 sm:p-5">
            <p class="text-[0.65rem] text-caramel font-semibold uppercase tracking-wider mb-2">Produk Terlaris</p>
            <p class="text-xl sm:text-2xl font-bold text-espresso truncate">{{ $topProduct?->product_name ?? '-' }}</p>
            @if($topProduct?->variant)
                <span class="text-xs text-caramel">({{ ucfirst($topProduct->variant) }})</span>
            @endif
        </div>
    </div>

    {{-- Top 5 Bar Chart --}}
    @if($top5->count() > 0)
    <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-4 sm:p-6 mb-6">
        <h4 class="text-sm font-bold text-espresso mb-4 sm:mb-5 flex items-center gap-2">
            <svg class="w-4.5 h-4.5 text-caramel" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>
            Top 5 Produk Terlaris
        </h4>
        <div class="space-y-4">
            @foreach($top5 as $i => $product)
                @php
                    $maxQty = $top5->first()->total_quantity;
                    $barWidth = $maxQty > 0 ? ($product->total_quantity / $maxQty) * 100 : 0;
                    $barColors = ['bg-espresso', 'bg-caramel-dark', 'bg-caramel', 'bg-caramel-light', 'bg-latte'];
                @endphp
                <div>
                    <div class="flex items-start sm:items-center justify-between mb-1 gap-2">
                        <span class="text-sm font-semibold text-espresso">
                            {{ $product->product_name }}
                            @if($product->variant)
                                <span class="text-xs text-caramel font-normal">({{ ucfirst($product->variant) }})</span>
                            @endif
                        </span>
                        <span class="text-xs font-bold text-espresso whitespace-nowrap">{{ $product->total_quantity }} item</span>
                    </div>
                    <div class="w-full bg-latte/30 rounded-full h-3">
                        <div class="h-3 rounded-full {{ $barColors[$i] ?? 'bg-latte' }} transition-all duration-700" style="width: {{ $barWidth }}%"></div>
                    </div>
                    <p class="text-xs text-caramel-dark mt-0.5">Rp {{ number_format($product->total_revenue, 0, ',', '.') }} &middot; {{ $product->total_orders }} pesanan</p>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Full Product List --}}
    <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-4 sm:p-6">
        <h4 class="text-sm font-bold text-espresso mb-4 flex items-center gap-2">
            <svg class="w-4.5 h-4.5 text-caramel" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z"/></svg>
            Detail Semua Produk
        </h4>

        @if($productStats->count() > 0)
            {{-- Mobile: Cards --}}
            <div class="sm:hidden space-y-3">
                @foreach($productStats as $i => $p)
                    <div class="p-3 rounded-xl border border-latte/30 bg-cream/20">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <span class="w-7 h-7 rounded-lg {{ $i < 3 ? 'bg-espresso text-cream' : 'bg-latte/50 text-espresso' }} flex items-center justify-center text-xs font-bold shrink-0">{{ $i + 1 }}</span>
                                <div>
                                    <span class="text-sm font-semibold text-espresso">{{ $p->product_name }}</span>
                                    @if($p->variant)
                                        <span class="text-xs text-caramel ml-1">({{ ucfirst($p->variant) }})</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-2 text-xs">
                            <div>
                                <span class="text-caramel block">Qty</span>
                                <span class="font-bold text-espresso">{{ $p->total_quantity }}</span>
                            </div>
                            <div>
                                <span class="text-caramel block">Pesanan</span>
                                <span class="font-bold text-espresso">{{ $p->total_orders }}</span>
                            </div>
                            <div>
                                <span class="text-caramel block">Revenue</span>
                                <span class="font-bold text-espresso">Rp {{ number_format($p->total_revenue, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Desktop: Table --}}
            <div class="hidden sm:block overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-latte/40">
                            <th class="text-left py-3 px-3 text-xs font-semibold text-caramel uppercase tracking-wider">#</th>
                            <th class="text-left py-3 px-3 text-xs font-semibold text-caramel uppercase tracking-wider">Produk</th>
                            <th class="text-left py-3 px-3 text-xs font-semibold text-caramel uppercase tracking-wider">Varian</th>
                            <th class="text-right py-3 px-3 text-xs font-semibold text-caramel uppercase tracking-wider">Qty</th>
                            <th class="text-right py-3 px-3 text-xs font-semibold text-caramel uppercase tracking-wider">Pesanan</th>
                            <th class="text-right py-3 px-3 text-xs font-semibold text-caramel uppercase tracking-wider">Pendapatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-latte/20">
                        @foreach($productStats as $i => $p)
                            <tr class="hover:bg-cream/30 transition-colors">
                                <td class="py-3 px-3 text-caramel-dark">{{ $i + 1 }}</td>
                                <td class="py-3 px-3 font-medium text-espresso">{{ $p->product_name }}</td>
                                <td class="py-3 px-3 text-caramel-dark">{{ $p->variant ? ucfirst($p->variant) : '-' }}</td>
                                <td class="py-3 px-3 text-right font-semibold text-espresso">{{ $p->total_quantity }}</td>
                                <td class="py-3 px-3 text-right text-caramel-dark">{{ $p->total_orders }}</td>
                                <td class="py-3 px-3 text-right font-semibold text-espresso">Rp {{ number_format($p->total_revenue, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-12 text-caramel-dark">
                <p class="text-sm">Belum ada data penjualan produk pada periode ini.</p>
            </div>
        @endif
    </div>
@endsection
