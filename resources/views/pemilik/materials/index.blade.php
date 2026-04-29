@extends('layouts.pemilik')

@section('title', 'Bahan Baku')
@section('page-title', 'Kelola Bahan Baku')
@section('page-description', 'Atur stok dan data bahan baku')

@section('content')
    <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-6">
        @include('components.coming-soon', [
            'title' => 'Manajemen Bahan Baku',
            'description' => 'Kelola seluruh data bahan baku, stok masuk, dan stok keluar.',
            'icon' => 'material',
            'features' => [
                'Tambah & edit bahan baku',
                'Pencatatan stok masuk',
                'Tracking stok keluar',
                'Notifikasi stok rendah',
                'Satuan & konversi',
            ]
        ])
    </div>
@endsection
