@extends('layouts.pemilik')

@section('title', 'Data Meja')
@section('page-title', 'Kelola Data Meja')
@section('page-description', 'Atur meja dan kapasitas tempat duduk')

@section('content')
    <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-6">
        @include('components.coming-soon', [
            'title' => 'Manajemen Data Meja',
            'description' => 'Kelola data meja beserta kapasitas dan statusnya.',
            'icon' => 'table',
            'features' => [
                'Tambah & edit data meja',
                'Nomor & nama meja',
                'Kapasitas per meja',
                'Status meja (tersedia/terisi)',
                'Layout/denah meja',
            ]
        ])
    </div>
@endsection
