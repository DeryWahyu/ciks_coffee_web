@extends('layouts.pemilik')

@section('title', 'Produk & Harga')
@section('page-title', 'Kelola Produk & Harga')
@section('page-description', 'Atur menu, kategori, dan harga produk')

@section('content')
    <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-6">
        @include('components.coming-soon', [
            'title' => 'Manajemen Produk & Harga',
            'description' => 'Kelola seluruh menu kopi, makanan, dan minuman beserta harga dan kategorinya.',
            'icon' => 'product',
            'features' => [
                'Tambah & edit produk',
                'Kategori produk (kopi, non-kopi, makanan)',
                'Pengaturan harga',
                'Upload foto produk',
                'Aktifkan/nonaktifkan produk',
            ]
        ])
    </div>
@endsection
