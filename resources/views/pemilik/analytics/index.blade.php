@extends('layouts.pemilik')

@section('title', 'Analisis Bisnis')
@section('page-title', 'Dashboard Analisis Bisnis')
@section('page-description', 'Insight dan analisis performa bisnis Ciks Coffee')

@section('content')
    <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-6">
        @include('components.coming-soon', [
            'title' => 'Dashboard Analisis Bisnis',
            'description' => 'Lihat insight mendalam tentang performa bisnis Ciks Coffee.',
            'icon' => 'analytics',
            'features' => [
                'Tren penjualan',
                'Analisis produk terlaris',
                'Jam sibuk & pola kunjungan',
                'Perbandingan periode',
                'KPI dashboard',
            ]
        ])
    </div>
@endsection
