@extends('layouts.pemilik')

@section('title', 'Laporan Penjualan')
@section('page-title', 'Laporan Penjualan')
@section('page-description', 'Laporan dan rekap penjualan harian, mingguan, dan bulanan')

@section('content')
    <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-6">
        @include('components.coming-soon', [
            'title' => 'Laporan Penjualan',
            'description' => 'Lihat laporan penjualan lengkap dengan filter tanggal dan kategori.',
            'icon' => 'sales',
            'features' => [
                'Laporan harian, mingguan, bulanan',
                'Filter berdasarkan tanggal',
                'Grafik penjualan',
                'Produk terlaris',
                'Rekap pendapatan',
            ]
        ])
    </div>
@endsection
