@extends('layouts.karyawan')

@section('title', 'Monitoring Meja')
@section('page-title', 'Monitoring Meja')
@section('page-description', 'Pantau status meja pelanggan')

@section('content')
    <div class="bg-espresso/5 border border-espresso/10 rounded-2xl p-8">
        <div class="flex flex-col items-center text-center">
            <div class="w-16 h-16 bg-latte/30 rounded-2xl flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-caramel" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h12A2.25 2.25 0 0120.25 6v12A2.25 2.25 0 0118 20.25H6A2.25 2.25 0 013.75 18V6z"/>
                </svg>
            </div>
            <h3 class="text-sm font-bold text-espresso mb-1">Fitur Monitoring Meja</h3>
            <p class="text-sm text-caramel-dark max-w-md">
                Fitur monitoring meja sedang dalam pengembangan. Anda akan dapat memantau status meja pelanggan secara real-time di sini.
            </p>
        </div>
    </div>
@endsection
