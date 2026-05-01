@extends('layouts.pemilik')

@section('title', 'Laporan Inventori & Stok')
@section('page-title', 'Inventori & Stok')
@section('page-description', 'Pantau ketersediaan bahan baku dan status stok')

@push('styles')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush

@section('content')
{{-- Summary Cards --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-5 relative overflow-hidden group">
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-latte/20 rounded-full group-hover:scale-110 transition-transform duration-500"></div>
        <div class="relative z-10">
            <div class="w-10 h-10 bg-latte/30 rounded-xl flex items-center justify-center mb-3">
                <svg class="w-5 h-5 text-espresso" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375"/></svg>
            </div>
            <p class="text-xs font-semibold text-caramel uppercase tracking-wider mb-1">Total Bahan Baku</p>
            <p class="text-2xl font-bold text-espresso">{{ number_format($totalIngredients) }}</p>
        </div>
    </div>
    
    <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-5 relative overflow-hidden group">
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-red-50 rounded-full group-hover:scale-110 transition-transform duration-500"></div>
        <div class="relative z-10">
            <div class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center mb-3">
                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <p class="text-xs font-semibold text-caramel uppercase tracking-wider mb-1">Stok Menipis (≤ 20)</p>
            <p class="text-2xl font-bold text-red-600">{{ number_format($lowStockCount) }}</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-5 relative overflow-hidden group">
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-green-50 rounded-full group-hover:scale-110 transition-transform duration-500"></div>
        <div class="relative z-10">
            <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center mb-3">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <p class="text-xs font-semibold text-caramel uppercase tracking-wider mb-1">Stok Aman</p>
            <p class="text-2xl font-bold text-green-600">{{ number_format($safeStockCount) }}</p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    {{-- Chart Section --}}
    <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-5 lg:col-span-2">
        <h3 class="font-bold text-espresso mb-4">Grafik 10 Bahan Baku dengan Stok Terendah</h3>
        <div class="relative h-72 w-full">
            <canvas id="inventoryChart"></canvas>
        </div>
    </div>

    {{-- Details Table --}}
    <div class="bg-white rounded-2xl shadow-sm border border-latte/50 overflow-hidden flex flex-col lg:col-span-2">
        <div class="p-5 border-b border-latte/40 flex items-center justify-between">
            <h3 class="font-bold text-espresso">Status Semua Bahan Baku</h3>
        </div>
        <div class="p-0 overflow-x-auto">
            @if($ingredients->isEmpty())
                <div class="p-8 text-center text-caramel-dark text-sm">Tidak ada data bahan baku.</div>
            @else
                <table class="w-full text-left border-collapse min-w-[500px]">
                    <thead>
                        <tr class="bg-latte/10 text-caramel border-b border-latte/40">
                            <th class="py-3 px-5 text-xs font-semibold uppercase tracking-wider">Nama Bahan</th>
                            <th class="py-3 px-5 text-xs font-semibold uppercase tracking-wider text-center">Satuan</th>
                            <th class="py-3 px-5 text-xs font-semibold uppercase tracking-wider text-right">Sisa Stok</th>
                            <th class="py-3 px-5 text-xs font-semibold uppercase tracking-wider text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-latte/30">
                        @foreach($ingredients as $ingredient)
                        <tr class="hover:bg-latte/5 transition-colors">
                            <td class="py-3 px-5 text-sm font-bold text-espresso">{{ $ingredient->nama_bahan }}</td>
                            <td class="py-3 px-5 text-sm text-caramel-dark text-center">{{ $ingredient->satuan }}</td>
                            <td class="py-3 px-5 text-sm font-semibold text-espresso text-right">
                                {{ number_format($ingredient->stok, $ingredient->stok == intval($ingredient->stok) ? 0 : 2, ',', '.') }}
                            </td>
                            <td class="py-3 px-5 text-center">
                                @if($ingredient->stok <= 20)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">Menipis</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Aman</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const chartDataRaw = @json($chartData);
        
        if (chartDataRaw.length > 0) {
            const labels = chartDataRaw.map(item => item.name);
            const data = chartDataRaw.map(item => item.stok);
            const backgroundColors = data.map(value => value <= 20 ? 'rgba(239, 68, 68, 0.7)' : 'rgba(161, 136, 127, 0.7)');
            const borderColors = data.map(value => value <= 20 ? 'rgb(220, 38, 38)' : 'rgb(141, 110, 99)');

            const ctx = document.getElementById('inventoryChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Sisa Stok',
                        data: data,
                        backgroundColor: backgroundColors,
                        borderColor: borderColors,
                        borderWidth: 1,
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        label += context.parsed.y + ' ' + chartDataRaw[context.dataIndex].satuan;
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(215, 204, 200, 0.3)',
                                drawBorder: false,
                            },
                            ticks: {
                                color: '#A1887F'
                            }
                        },
                        x: {
                            grid: {
                                display: false,
                                drawBorder: false,
                            },
                            ticks: {
                                color: '#3E2723',
                                font: {
                                    weight: 'bold'
                                }
                            }
                        }
                    }
                }
            });
        }
    });
</script>
@endpush
@endsection
