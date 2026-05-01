@extends('layouts.pemilik')
@section('title', 'Pelanggan')
@section('page-title', 'Data Pelanggan')
@section('page-description', 'Catatan pelanggan yang sering membeli')

@section('content')
{{-- Stats Cards --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4 mb-6">
    <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-3 sm:p-4">
        <div class="w-8 h-8 bg-espresso/10 rounded-lg flex items-center justify-center mb-2">
            <svg class="w-4 h-4 text-espresso" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
        </div>
        <p class="text-lg sm:text-xl font-bold text-espresso">{{ number_format($stats['unique_customers']) }}</p>
        <p class="text-[0.6rem] sm:text-[0.65rem] text-caramel font-semibold uppercase tracking-wider mt-0.5">Total Pelanggan</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-3 sm:p-4">
        <div class="w-8 h-8 bg-amber-50 rounded-lg flex items-center justify-center mb-2">
            <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/></svg>
        </div>
        <p class="text-lg sm:text-xl font-bold text-espresso truncate">{{ $stats['top_customer'] }}</p>
        <p class="text-[0.6rem] sm:text-[0.65rem] text-caramel font-semibold uppercase tracking-wider mt-0.5">Pelanggan Teratas</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-3 sm:p-4">
        <div class="w-8 h-8 bg-green-50 rounded-lg flex items-center justify-center mb-2">
            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <p class="text-lg sm:text-xl font-bold text-espresso">{{ $stats['top_customer_orders'] }}x</p>
        <p class="text-[0.6rem] sm:text-[0.65rem] text-caramel font-semibold uppercase tracking-wider mt-0.5">Order Terbanyak</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-3 sm:p-4">
        <div class="w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center mb-2">
            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12c0-1.232-.046-2.453-.138-3.662a4.006 4.006 0 00-3.7-3.7 48.678 48.678 0 00-7.324 0 4.006 4.006 0 00-3.7 3.7c-.017.22-.032.441-.046.662M19.5 12l3-3m-3 3l-3-3m-12 3c0 1.232.046 2.453.138 3.662a4.006 4.006 0 003.7 3.7 48.656 48.656 0 007.324 0 4.006 4.006 0 003.7-3.7c.017-.22.032-.441.046-.662M4.5 12l3 3m-3-3l-3 3"/></svg>
        </div>
        <p class="text-lg sm:text-xl font-bold text-espresso">{{ number_format($stats['returning_customers']) }}</p>
        <p class="text-[0.6rem] sm:text-[0.65rem] text-caramel font-semibold uppercase tracking-wider mt-0.5">Pelanggan Setia</p>
    </div>
</div>

{{-- Period Tabs & Search --}}
<div class="space-y-3 sm:space-y-0 sm:flex sm:flex-wrap sm:items-center sm:justify-between sm:gap-3 mb-6">
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('pemilik.customers.index', ['period' => 'all']) }}" class="px-3 sm:px-4 py-2 text-xs font-semibold rounded-xl border transition-all {{ $period === 'all' ? 'bg-espresso text-cream border-espresso' : 'bg-white text-caramel border-latte/50 hover:border-caramel' }}">Semua Waktu</a>
        <a href="{{ route('pemilik.customers.index', ['period' => 'month']) }}" class="px-3 sm:px-4 py-2 text-xs font-semibold rounded-xl border transition-all {{ $period === 'month' ? 'bg-espresso text-cream border-espresso' : 'bg-white text-caramel border-latte/50 hover:border-caramel' }}">30 Hari</a>
        <a href="{{ route('pemilik.customers.index', ['period' => 'week']) }}" class="px-3 sm:px-4 py-2 text-xs font-semibold rounded-xl border transition-all {{ $period === 'week' ? 'bg-espresso text-cream border-espresso' : 'bg-white text-caramel border-latte/50 hover:border-caramel' }}">7 Hari</a>
    </div>
    <form method="GET" action="{{ route('pemilik.customers.index') }}" class="flex items-center gap-2">
        <input type="hidden" name="period" value="{{ $period }}">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari pelanggan..." class="flex-1 sm:w-48 px-3 py-2 text-sm border border-latte/60 rounded-xl focus:ring-2 focus:ring-caramel/30 focus:border-caramel outline-none">
        <button type="submit" class="px-3 py-2 text-sm font-semibold bg-espresso text-cream rounded-xl hover:bg-espresso-light transition">Cari</button>
    </form>
</div>

{{-- Customer Cards (works on all screen sizes) --}}
<div class="space-y-3">
    @forelse ($customers as $index => $customer)
    @php $rank = $customers->firstItem() + $index; @endphp
    <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-4 sm:p-5 hover:shadow-md transition-all duration-300">
        <div class="flex flex-wrap items-start gap-3 sm:gap-4">
            {{-- Rank --}}
            <div class="shrink-0">
                @if ($rank === 1)
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-amber-100 text-lg">🥇</span>
                @elseif ($rank === 2)
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-gray-100 text-lg">🥈</span>
                @elseif ($rank === 3)
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-orange-100 text-lg">🥉</span>
                @else
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-latte/30 text-sm font-bold text-espresso">{{ $rank }}</span>
                @endif
            </div>

            {{-- Name & Badge --}}
            <div class="flex-1 min-w-0">
                <div class="flex flex-wrap items-center gap-2 mb-1">
                    <p class="text-sm font-bold text-espresso">{{ $customer->customer_name }}</p>
                    <span class="inline-flex items-center justify-center min-w-[2rem] px-2 py-0.5 rounded-full text-xs font-bold {{ $customer->total_orders > 5 ? 'bg-espresso text-cream' : ($customer->total_orders > 2 ? 'bg-caramel/20 text-espresso' : 'bg-latte/30 text-espresso') }}">
                        {{ $customer->total_orders }}x order
                    </span>
                    @if ($customer->total_orders > 5)
                        <span class="text-[0.6rem] bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded-full font-bold">Pelanggan Setia ⭐</span>
                    @elseif ($customer->total_orders > 2)
                        <span class="text-[0.6rem] bg-green-100 text-green-700 px-1.5 py-0.5 rounded-full font-bold">Returning</span>
                    @endif
                </div>

                {{-- Stats Row --}}
                <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs mt-2">
                    <div>
                        <span class="text-caramel">Total Belanja:</span>
                        <span class="font-semibold text-espresso">Rp {{ number_format($customer->total_spent, 0, ',', '.') }}</span>
                    </div>
                    <div>
                        <span class="text-caramel">Rata-rata:</span>
                        <span class="font-semibold text-espresso">Rp {{ number_format($customer->avg_spent, 0, ',', '.') }}</span>
                    </div>
                    <div>
                        <span class="text-caramel">Pertama:</span>
                        <span class="text-caramel-dark">{{ \Carbon\Carbon::parse($customer->first_order_at)->format('d/m/Y') }}</span>
                    </div>
                    <div>
                        <span class="text-caramel">Terakhir:</span>
                        <span class="text-caramel-dark">{{ \Carbon\Carbon::parse($customer->last_order_at)->format('d/m/Y H:i') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-12 text-center">
        <div class="w-14 h-14 bg-latte/30 rounded-2xl flex items-center justify-center mb-3 mx-auto">
            <svg class="w-7 h-7 text-caramel" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
        </div>
        <p class="text-sm text-caramel-dark font-medium">Belum ada data pelanggan</p>
    </div>
    @endforelse
</div>

@if ($customers->hasPages())
    <div class="mt-6">{{ $customers->links() }}</div>
@endif
@endsection
