@extends('layouts.pemilik')

@section('title', 'Laporan Penjualan')
@section('page-title', 'Laporan Penjualan')
@section('page-description', 'Rekapitulasi transaksi dan pendapatan')

@section('content')
{{-- Filter Section --}}
<div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-4 sm:p-6 mb-6">
    <form method="GET" action="{{ route('pemilik.reports.sales') }}" class="flex flex-col sm:flex-row sm:items-end gap-4">
        <div class="flex-1">
            <label class="block text-xs font-semibold text-caramel-dark mb-1.5 uppercase tracking-wider">Tanggal Mulai</label>
            <input type="date" name="start_date" value="{{ $startDate }}" class="w-full px-3 py-2 text-sm border border-latte/60 rounded-xl focus:ring-2 focus:ring-caramel/30 focus:border-caramel outline-none text-espresso">
        </div>
        <div class="flex-1">
            <label class="block text-xs font-semibold text-caramel-dark mb-1.5 uppercase tracking-wider">Tanggal Akhir</label>
            <input type="date" name="end_date" value="{{ $endDate }}" class="w-full px-3 py-2 text-sm border border-latte/60 rounded-xl focus:ring-2 focus:ring-caramel/30 focus:border-caramel outline-none text-espresso">
        </div>
        <div class="sm:w-32">
            <button type="submit" class="w-full px-4 py-2 text-sm font-semibold bg-espresso text-cream rounded-xl hover:bg-espresso-light transition">Terapkan</button>
        </div>
    </form>
</div>

{{-- Summary Cards --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-5 relative overflow-hidden group">
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-green-50 rounded-full group-hover:scale-110 transition-transform duration-500"></div>
        <div class="relative z-10">
            <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center mb-3">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <p class="text-xs font-semibold text-caramel uppercase tracking-wider mb-1">Total Pendapatan</p>
            <p class="text-2xl font-bold text-espresso">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</p>
        </div>
    </div>
    
    <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-5 relative overflow-hidden group">
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-blue-50 rounded-full group-hover:scale-110 transition-transform duration-500"></div>
        <div class="relative z-10">
            <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center mb-3">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 010 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 010-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375z"/></svg>
            </div>
            <p class="text-xs font-semibold text-caramel uppercase tracking-wider mb-1">Total Pesanan</p>
            <p class="text-2xl font-bold text-espresso">{{ number_format($totalOrders) }}</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-5 relative overflow-hidden group">
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-amber-50 rounded-full group-hover:scale-110 transition-transform duration-500"></div>
        <div class="relative z-10">
            <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center mb-3">
                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
            </div>
            <p class="text-xs font-semibold text-caramel uppercase tracking-wider mb-1">Total Pelanggan</p>
            <p class="text-2xl font-bold text-espresso">{{ number_format($totalCustomers) }}</p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    {{-- Sales By Date --}}
    <div class="bg-white rounded-2xl shadow-sm border border-latte/50 overflow-hidden flex flex-col">
        <div class="p-5 border-b border-latte/40 flex items-center justify-between">
            <h3 class="font-bold text-espresso">Pendapatan Harian</h3>
            <span class="text-xs px-2.5 py-1 bg-latte/30 text-espresso rounded-lg font-medium">Periode Aktif</span>
        </div>
        <div class="p-0 flex-1 overflow-x-auto">
            @if($salesByDate->isEmpty())
                <div class="p-8 text-center text-caramel-dark text-sm">Tidak ada data transaksi pada rentang tanggal ini.</div>
            @else
                <table class="w-full text-left border-collapse min-w-[300px]">
                    <thead>
                        <tr class="bg-latte/10 text-caramel border-b border-latte/40">
                            <th class="py-3 px-5 text-xs font-semibold uppercase tracking-wider w-1/3">Tanggal</th>
                            <th class="py-3 px-5 text-xs font-semibold uppercase tracking-wider text-center w-1/3">Pesanan</th>
                            <th class="py-3 px-5 text-xs font-semibold uppercase tracking-wider text-right w-1/3">Pendapatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-latte/30">
                        @foreach($salesByDate as $sale)
                        <tr class="hover:bg-latte/5 transition-colors">
                            <td class="py-3 px-5 text-sm text-espresso font-medium">{{ \Carbon\Carbon::parse($sale->date)->format('d M Y') }}</td>
                            <td class="py-3 px-5 text-sm text-espresso text-center">{{ $sale->orders_count }}</td>
                            <td class="py-3 px-5 text-sm font-semibold text-green-600 text-right">Rp {{ number_format($sale->revenue, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    {{-- Top Products --}}
    <div class="bg-white rounded-2xl shadow-sm border border-latte/50 overflow-hidden flex flex-col">
        <div class="p-5 border-b border-latte/40 flex items-center justify-between">
            <h3 class="font-bold text-espresso">Produk Terlaris (Top 10)</h3>
            <span class="text-xs px-2.5 py-1 bg-amber-100 text-amber-800 rounded-lg font-bold">Terpopuler</span>
        </div>
        <div class="p-0 flex-1 overflow-x-auto">
            @if($topProducts->isEmpty())
                <div class="p-8 text-center text-caramel-dark text-sm">Belum ada produk yang terjual.</div>
            @else
                <table class="w-full text-left border-collapse min-w-[300px]">
                    <thead>
                        <tr class="bg-latte/10 text-caramel border-b border-latte/40">
                            <th class="py-3 px-5 text-xs font-semibold uppercase tracking-wider">Produk</th>
                            <th class="py-3 px-5 text-xs font-semibold uppercase tracking-wider text-center">Terjual</th>
                            <th class="py-3 px-5 text-xs font-semibold uppercase tracking-wider text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-latte/30">
                        @foreach($topProducts as $index => $product)
                        <tr class="hover:bg-latte/5 transition-colors">
                            <td class="py-3 px-5">
                                <div class="flex items-center gap-3">
                                    <span class="w-6 h-6 rounded-full bg-latte/40 text-espresso text-xs font-bold flex items-center justify-center shrink-0">{{ $index + 1 }}</span>
                                    <div>
                                        <p class="text-sm font-bold text-espresso">{{ $product->product_name }}</p>
                                        @if($product->variant)
                                            <p class="text-[0.65rem] text-caramel font-semibold uppercase tracking-wider">{{ $product->variant }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 px-5 text-sm text-espresso font-medium text-center">
                                {{ $product->total_quantity }} <span class="text-xs text-caramel font-normal">pcs</span>
                            </td>
                            <td class="py-3 px-5 text-sm font-semibold text-espresso text-right">
                                Rp {{ number_format($product->total_revenue, 0, ',', '.') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
@endsection
