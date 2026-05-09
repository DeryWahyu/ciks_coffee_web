@extends('layouts.pemilik')

@section('title', 'Detail Karyawan - ' . $user->name)
@section('page-title', 'Detail Performa: ' . $user->name)
@section('page-description', 'Rincian penjualan karyawan pada ' . \Carbon\Carbon::parse($startDate)->translatedFormat('d M Y') . ' - ' . \Carbon\Carbon::parse($endDate)->translatedFormat('d M Y'))

@section('page-actions')
    <a href="{{ route('pemilik.performance.employees', ['start_date' => $startDate, 'end_date' => $endDate]) }}" class="px-4 py-2 text-sm font-semibold text-espresso bg-latte/30 rounded-xl hover:bg-latte/50 transition-all flex items-center gap-1.5">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
        Kembali
    </a>
@endsection

@section('content')
    {{-- Employee Summary --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-5">
            <p class="text-[0.65rem] text-caramel font-semibold uppercase tracking-wider mb-2">Total Pesanan</p>
            <p class="text-2xl font-bold text-espresso">{{ number_format($summary->total_orders ?? 0) }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-5">
            <p class="text-[0.65rem] text-caramel font-semibold uppercase tracking-wider mb-2">Total Pendapatan</p>
            <p class="text-2xl font-bold text-espresso">Rp {{ number_format($summary->total_revenue ?? 0, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-5">
            <p class="text-[0.65rem] text-caramel font-semibold uppercase tracking-wider mb-2">Rata-rata Transaksi</p>
            <p class="text-2xl font-bold text-espresso">Rp {{ number_format($summary->avg_order ?? 0, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-5">
            <p class="text-[0.65rem] text-caramel font-semibold uppercase tracking-wider mb-2">Produk Dijual</p>
            <p class="text-2xl font-bold text-espresso">{{ $productsSold->count() }} <span class="text-sm font-normal text-caramel-dark">jenis</span></p>
        </div>
    </div>

    {{-- Filter & Header --}}
    <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-4 sm:p-5 mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h2 class="text-lg font-bold text-espresso">{{ $user->name }}</h2>
            <p class="text-sm text-caramel-dark mt-0.5">Laporan Kinerja &middot; {{ \Carbon\Carbon::parse($startDate)->translatedFormat('d M Y') }} - {{ \Carbon\Carbon::parse($endDate)->translatedFormat('d M Y') }}</p>
        </div>
        <form method="GET" action="{{ route('pemilik.performance.employee-detail', $user->id) }}" class="flex flex-col sm:flex-row items-end gap-3 w-full md:w-auto">
            <div class="w-full sm:w-auto flex flex-col sm:flex-row gap-3">
                <div class="w-full sm:w-auto">
                    <label class="block text-[0.65rem] font-semibold text-caramel uppercase tracking-wider mb-1">Dari Tanggal</label>
                    <input type="date" name="start_date" value="{{ $startDate }}" class="w-full px-3 py-2 bg-cream-light border border-latte rounded-xl text-sm text-espresso focus:outline-none focus:ring-2 focus:ring-caramel/30 focus:border-caramel transition-all">
                </div>
                <div class="w-full sm:w-auto">
                    <label class="block text-[0.65rem] font-semibold text-caramel uppercase tracking-wider mb-1">Sampai Tanggal</label>
                    <input type="date" name="end_date" value="{{ $endDate }}" class="w-full px-3 py-2 bg-cream-light border border-latte rounded-xl text-sm text-espresso focus:outline-none focus:ring-2 focus:ring-caramel/30 focus:border-caramel transition-all">
                </div>
            </div>
            <button type="submit" class="w-full sm:w-auto px-5 py-2 bg-espresso text-cream font-semibold text-sm rounded-xl hover:bg-espresso-light transition-all duration-200 shadow-sm">Filter</button>
        </form>
    </div>

    {{-- Products Sold Table --}}
    <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-6 mb-6">
        <h4 class="text-sm font-bold text-espresso mb-4 flex items-center gap-2">
            <svg class="w-4.5 h-4.5 text-caramel" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
            Produk Yang Dijual
        </h4>

        @if($productsSold->count() > 0)
            {{-- Mobile: Cards --}}
            <div class="sm:hidden space-y-3">
                @foreach($productsSold as $i => $p)
                    <div class="p-3 rounded-xl border border-latte/30 bg-cream/20">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <span class="w-7 h-7 rounded-lg {{ $i < 3 ? 'bg-espresso text-cream' : 'bg-latte/50 text-espresso' }} flex items-center justify-center text-xs font-bold">{{ $i + 1 }}</span>
                                <span class="text-sm font-semibold text-espresso">{{ $p->product_name }}</span>
                            </div>
                            @if($p->variant)
                                <span class="text-xs text-caramel bg-latte/30 px-2 py-0.5 rounded-full">{{ ucfirst($p->variant) }}</span>
                            @endif
                        </div>
                        <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-caramel-dark">
                            <span>Jml: <span class="font-semibold text-espresso">{{ $p->total_quantity }}</span></span>
                            <span>Pesanan: <span class="font-semibold text-espresso">{{ $p->total_orders }}</span></span>
                            <span>Pendapatan: <span class="font-semibold text-espresso">Rp {{ number_format($p->total_revenue, 0, ',', '.') }}</span></span>
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
                            <th class="text-right py-3 px-3 text-xs font-semibold text-caramel uppercase tracking-wider">Jml Terjual</th>
                            <th class="text-right py-3 px-3 text-xs font-semibold text-caramel uppercase tracking-wider">Pesanan</th>
                            <th class="text-right py-3 px-3 text-xs font-semibold text-caramel uppercase tracking-wider">Pendapatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-latte/20">
                        @foreach($productsSold as $i => $p)
                            <tr class="hover:bg-cream/30 transition-colors">
                                <td class="py-3 px-3">
                                    <span class="w-7 h-7 rounded-lg {{ $i < 3 ? 'bg-espresso text-cream' : 'bg-latte/50 text-espresso' }} inline-flex items-center justify-center text-xs font-bold">{{ $i + 1 }}</span>
                                </td>
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
            <div class="text-center py-10 text-caramel-dark">
                <p class="text-sm">Belum ada produk yang dijual oleh karyawan ini pada periode ini.</p>
            </div>
        @endif
    </div>

    {{-- Pesanan Terbaru --}}
    <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4 mb-4">
            <h4 class="text-sm font-bold text-espresso flex items-center gap-2">
                <svg class="w-4.5 h-4.5 text-caramel" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Riwayat Transaksi Terakhir (Maks 20)
            </h4>
            <form method="GET" action="{{ route('pemilik.performance.employee-detail', $user->id) }}" class="flex flex-col sm:flex-row items-end gap-3 w-full sm:w-auto">
                <div class="w-full sm:w-auto flex flex-col sm:flex-row gap-3">
                    <div class="w-full sm:w-auto">
                        <label class="block text-[0.65rem] font-semibold text-caramel uppercase tracking-wider mb-1">Dari Tanggal</label>
                        <input type="date" name="start_date" value="{{ $startDate }}" class="w-full px-3 py-2 bg-cream-light border border-latte rounded-xl text-sm text-espresso focus:outline-none focus:ring-2 focus:ring-caramel/30 focus:border-caramel transition-all">
                    </div>
                    <div class="w-full sm:w-auto">
                        <label class="block text-[0.65rem] font-semibold text-caramel uppercase tracking-wider mb-1">Sampai Tanggal</label>
                        <input type="date" name="end_date" value="{{ $endDate }}" class="w-full px-3 py-2 bg-cream-light border border-latte rounded-xl text-sm text-espresso focus:outline-none focus:ring-2 focus:ring-caramel/30 focus:border-caramel transition-all">
                    </div>
                </div>
                <button type="submit" class="w-full sm:w-auto px-5 py-2 bg-espresso text-cream font-semibold text-sm rounded-xl hover:bg-espresso-light transition-all duration-200 shadow-sm">Filter</button>
            </form>
        </div>

        @if($recentOrders->count() > 0)
            {{-- Mobile: Cards --}}
            <div class="sm:hidden space-y-3">
                @foreach($recentOrders as $order)
                    <div class="p-3 rounded-xl border border-latte/30 bg-cream/20">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold text-espresso">{{ $order->order_number }}</span>
                            <span class="text-xs text-caramel-dark">{{ $order->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <p class="text-xs text-caramel-dark mb-1">{{ $order->customer_name }} &middot; {{ strtoupper($order->payment_method) }}</p>
                        <div class="text-xs text-caramel-dark">
                            @foreach($order->items as $item)
                                <span class="inline-block mr-2">{{ $item->product_name }}{{ $item->variant ? ' ('.$item->variant.')' : '' }} x{{ $item->quantity }}</span>
                            @endforeach
                        </div>
                        <p class="text-sm font-bold text-espresso mt-2">Rp {{ number_format($order->total, 0, ',', '.') }}</p>
                    </div>
                @endforeach
            </div>

            {{-- Desktop: Table --}}
            <div class="hidden sm:block overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-latte/40">
                            <th class="text-left py-3 px-3 text-xs font-semibold text-caramel uppercase tracking-wider">No. Order</th>
                            <th class="text-left py-3 px-3 text-xs font-semibold text-caramel uppercase tracking-wider">Pelanggan</th>
                            <th class="text-left py-3 px-3 text-xs font-semibold text-caramel uppercase tracking-wider">Produk</th>
                            <th class="text-center py-3 px-3 text-xs font-semibold text-caramel uppercase tracking-wider">Bayar</th>
                            <th class="text-right py-3 px-3 text-xs font-semibold text-caramel uppercase tracking-wider">Total</th>
                            <th class="text-right py-3 px-3 text-xs font-semibold text-caramel uppercase tracking-wider">Waktu</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-latte/20">
                        @foreach($recentOrders as $order)
                            <tr class="hover:bg-cream/30 transition-colors">
                                <td class="py-3 px-3 font-mono text-xs font-semibold text-espresso">{{ $order->order_number }}</td>
                                <td class="py-3 px-3 text-espresso">{{ $order->customer_name }}</td>
                                <td class="py-3 px-3 text-caramel-dark text-xs">
                                    @foreach($order->items as $item)
                                        <span class="inline-block mr-1">{{ $item->product_name }}{{ $item->variant ? ' ('.$item->variant.')' : '' }} x{{ $item->quantity }}{{ !$loop->last ? ',' : '' }}</span>
                                    @endforeach
                                </td>
                                <td class="py-3 px-3 text-center">
                                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $order->payment_method === 'cash' ? 'bg-green-50 text-green-700' : 'bg-blue-50 text-blue-700' }}">{{ strtoupper($order->payment_method) }}</span>
                                </td>
                                <td class="py-3 px-3 text-right font-semibold text-espresso">Rp {{ number_format($order->total, 0, ',', '.') }}</td>
                                <td class="py-3 px-3 text-right text-xs text-caramel-dark">{{ $order->created_at->format('d/m H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-10 text-caramel-dark">
                <p class="text-sm">Belum ada transaksi pada periode ini.</p>
            </div>
        @endif
    </div>
@endsection
