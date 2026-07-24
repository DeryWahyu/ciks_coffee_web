@extends('layouts.karyawan')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard Karyawan')
@section('page-description', 'Selamat bekerja di Ciks Coffee!')

@section('content')
    {{-- Welcome Card --}}
    <div class="mb-4 rounded-2xl border border-latte/50 bg-white p-4 shadow-sm sm:mb-6 sm:p-6">
        <div class="flex items-start gap-4">
            <div>
                <h3 class="break-words text-lg font-bold text-espresso sm:text-xl">Selamat Datang, {{ Auth::user()->name }}!</h3>
                <p class="mt-1 text-xs leading-5 text-caramel-dark sm:text-sm">Berikut ringkasan aktivitas kerja Anda hari ini, {{ now()->translatedFormat('l, d F Y') }}.</p>
            </div>
        </div>
    </div>

    {{-- Quick Stats --}}
    <div class="mb-4 grid grid-cols-1 gap-4 sm:mb-6 sm:grid-cols-2 sm:gap-5 lg:grid-cols-4">
        <div class="rounded-2xl border border-latte/50 bg-white p-4 shadow-sm transition-all duration-300 hover:shadow-md sm:p-5">
            <div class="mb-3 flex items-start justify-between gap-3">
                <div class="w-10 h-10 bg-espresso/10 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-espresso" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z"/>
                    </svg>
                </div>
                <span class="text-[0.65rem] text-caramel font-semibold uppercase tracking-wider">Pesanan</span>
            </div>
            <p class="break-words text-xl font-bold text-espresso sm:text-2xl">{{ $todayStats['total_orders'] }}</p>
            <p class="text-xs text-caramel-dark mt-1">Pesanan hari ini</p>
        </div>

        <div class="rounded-2xl border border-latte/50 bg-white p-4 shadow-sm transition-all duration-300 hover:shadow-md sm:p-5">
            <div class="mb-3 flex items-start justify-between gap-3">
                <div class="w-10 h-10 bg-caramel/15 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-caramel-dark" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-[0.65rem] text-caramel font-semibold uppercase tracking-wider">Pending</span>
            </div>
            <p class="break-words text-xl font-bold text-espresso sm:text-2xl">{{ $todayStats['pending_orders'] }}</p>
            <p class="text-xs text-caramel-dark mt-1">Menunggu proses</p>
        </div>

        <div class="rounded-2xl border border-latte/50 bg-white p-4 shadow-sm transition-all duration-300 hover:shadow-md sm:p-5">
            <div class="mb-3 flex items-start justify-between gap-3">
                <div class="w-10 h-10 bg-green-50 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-[0.65rem] text-caramel font-semibold uppercase tracking-wider">Selesai</span>
            </div>
            <p class="break-words text-xl font-bold text-espresso sm:text-2xl">{{ $todayStats['completed_orders'] }}</p>
            <p class="text-xs text-caramel-dark mt-1">Pesanan selesai</p>
        </div>
        
        <div class="rounded-2xl border border-latte/50 bg-white p-4 shadow-sm transition-all duration-300 hover:shadow-md sm:p-5">
            <div class="mb-3 flex items-start justify-between gap-3">
                <div class="w-10 h-10 bg-amber-50 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <span class="text-[0.65rem] text-caramel font-semibold uppercase tracking-wider">Penghasilan</span>
            </div>
            <p class="break-words text-xl font-bold text-espresso sm:text-2xl">Rp {{ number_format($todayStats['revenue'], 0, ',', '.') }}</p>
            <p class="text-xs text-caramel-dark mt-1">Pendapatan hari ini</p>
        </div>
    </div>

    {{-- Revenue Chart --}}
    <div class="mb-4 rounded-2xl border border-latte/50 bg-white p-4 shadow-sm sm:mb-6 sm:p-6">
        <div class="mb-4 flex items-start justify-between gap-3 sm:mb-6">
            <div>
                <h3 class="text-sm font-bold text-espresso mb-1">Pendapatan 7 Hari Terakhir</h3>
                <p class="text-xs text-caramel-dark mt-1">Grafik aktivitas penjualan</p>
            </div>
            <div class="w-10 h-10 bg-caramel/15 rounded-xl flex items-center justify-center">
                <svg class="w-5 h-5 text-caramel-dark" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                </svg>
            </div>
        </div>
        <div class="h-56 w-full sm:h-72">
            <canvas id="revenueChart"></canvas>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('revenueChart').getContext('2d');
        
        // Gradient for chart area
        let gradient = ctx.createLinearGradient(0, 0, 0, 300);
        gradient.addColorStop(0, 'rgba(180, 117, 85, 0.4)'); // Caramel
        gradient.addColorStop(1, 'rgba(180, 117, 85, 0.0)');
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: {!! json_encode($chart['labels']) !!},
                datasets: [{
                    label: 'Pendapatan (Rp)',
                    data: {!! json_encode($chart['data']) !!},
                    borderColor: '#B47555', // Caramel color
                    backgroundColor: gradient,
                    borderWidth: 3,
                    pointBackgroundColor: '#FFFFFF',
                    pointBorderColor: '#B47555',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    fill: true,
                    tension: 0.4 // Smooth curve
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
                        backgroundColor: '#3C2A21',
                        titleColor: '#F9F6F0',
                        bodyColor: '#F9F6F0',
                        padding: 12,
                        cornerRadius: 8,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(context.parsed.y);
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false,
                            drawBorder: false
                        },
                        ticks: {
                            color: '#8c7d75',
                            font: {
                                family: "'Inter', sans-serif",
                                size: 12
                            }
                        }
                    },
                    y: {
                        grid: {
                            color: '#f0e9e4',
                            borderDash: [5, 5],
                            drawBorder: false
                        },
                        ticks: {
                            color: '#8c7d75',
                            font: {
                                family: "'Inter', sans-serif",
                                size: 12
                            },
                            callback: function(value) {
                                return 'Rp ' + (value / 1000) + 'k';
                            }
                        },
                        beginAtZero: true
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index',
                },
            }
        });
    });
</script>
@endpush
