@extends('layouts.pemilik')

@section('title', 'Ekspor Data')
@section('page-title', 'Ekspor Data')
@section('page-description', 'Unduh data dalam format CSV, Excel, atau PDF')

@section('content')
    <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-6">
        @include('components.coming-soon', [
            'title' => 'Ekspor Data',
            'description' => 'Unduh data penjualan, inventori, dan laporan lainnya dalam berbagai format.',
            'icon' => 'export',
            'features' => [
                'Ekspor ke CSV',
                'Ekspor ke Excel (XLSX)',
                'Ekspor ke PDF',
                'Filter data sebelum ekspor',
                'Jadwal ekspor otomatis',
            ]
        ])
    </div>
@endsection
