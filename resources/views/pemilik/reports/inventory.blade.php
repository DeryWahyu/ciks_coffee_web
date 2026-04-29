@extends('layouts.pemilik')

@section('title', 'Inventori & Stok')
@section('page-title', 'Laporan Inventori & Stok Bahan Baku')
@section('page-description', 'Monitoring stok dan penggunaan bahan baku')

@section('content')
    <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-6">
        @include('components.coming-soon', [
            'title' => 'Laporan Inventori & Stok',
            'description' => 'Monitor stok bahan baku, riwayat penggunaan, dan prediksi kebutuhan.',
            'icon' => 'inventory',
            'features' => [
                'Status stok real-time',
                'Riwayat penggunaan bahan',
                'Peringatan stok minimum',
                'Laporan pemborosan',
                'Prediksi kebutuhan restok',
            ]
        ])
    </div>
@endsection
