@extends('layouts.pemilik')

@section('title', 'Produk & Harga')
@section('page-title', 'Kelola Produk & Harga')
@section('page-description', 'Atur menu, kategori, dan harga produk')

@section('page-actions')
    <div class="flex items-center gap-2">
        <a href="{{ route('pemilik.categories.index') }}" class="inline-flex items-center gap-2 bg-caramel/20 hover:bg-caramel/30 text-espresso text-sm font-medium px-4 py-2.5 rounded-xl transition-all duration-200" id="btn-categories">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z"/></svg>
            Kategori
        </a>
        <a href="{{ route('pemilik.products.create') }}" class="inline-flex items-center gap-2 bg-espresso hover:bg-espresso-light text-cream text-sm font-medium px-4 py-2.5 rounded-xl transition-all duration-200 hover:shadow-md" id="btn-add-product">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Tambah Produk
        </a>
    </div>
@endsection

@section('content')
    {{-- Filters --}}
    <div class="mb-6 flex flex-wrap items-center gap-3">
        <form method="GET" action="{{ route('pemilik.products.index') }}" class="flex flex-wrap items-center gap-3 w-full">
            <div class="relative flex-1 min-w-[200px]">
                <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-caramel" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari produk..." class="w-full pl-10 pr-4 py-2.5 text-sm bg-white border border-latte/60 rounded-xl focus:ring-2 focus:ring-caramel/30 focus:border-caramel outline-none transition">
            </div>
            <select name="category" class="bg-white border border-latte/60 text-sm text-espresso rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-caramel/30 focus:border-caramel outline-none">
                <option value="">Semua Kategori</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </select>
            <select name="status" class="bg-white border border-latte/60 text-sm text-espresso rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-caramel/30 focus:border-caramel outline-none">
                <option value="">Semua Status</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
            </select>
            <button type="submit" class="bg-espresso/10 hover:bg-espresso/20 text-espresso text-sm font-medium px-4 py-2.5 rounded-xl transition">Filter</button>
            @if(request()->hasAny(['search', 'category', 'status']))
                <a href="{{ route('pemilik.products.index') }}" class="text-sm text-caramel hover:text-espresso transition">Reset</a>
            @endif
        </form>
    </div>

    {{-- Product Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
        @forelse ($products as $product)
            <div class="bg-white rounded-2xl shadow-sm border border-latte/50 overflow-hidden group hover:shadow-md transition-all duration-300 {{ !$product->is_active ? 'opacity-60' : '' }}" id="product-{{ $product->id }}">
                {{-- Image --}}
                <div class="relative aspect-[4/3] bg-latte/20 overflow-hidden">
                    @if ($product->image)
                        <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    @else
                        <div class="w-full h-full flex items-center justify-center">
                            <svg class="w-12 h-12 text-caramel/40" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5a2.25 2.25 0 002.25-2.25V5.25a2.25 2.25 0 00-2.25-2.25H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21z"/></svg>
                        </div>
                    @endif
                    {{-- Status Badge --}}
                    <div class="absolute top-3 left-3">
                        <span class="px-2.5 py-1 rounded-full text-[0.65rem] font-bold uppercase tracking-wider {{ $product->is_active ? 'bg-green-500/90 text-white' : 'bg-red-500/90 text-white' }}">
                            {{ $product->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </div>
                    {{-- Category Badge --}}
                    <div class="absolute top-3 right-3">
                        <span class="px-2.5 py-1 rounded-full text-[0.65rem] font-bold uppercase tracking-wider bg-espresso/80 text-cream backdrop-blur-sm">
                            {{ $product->category->name }}
                        </span>
                    </div>
                </div>

                {{-- Info --}}
                <div class="p-4">
                    <h3 class="text-sm font-bold text-espresso mb-1 truncate">{{ $product->name }}</h3>
                    @if ($product->description)
                        <p class="text-xs text-caramel-dark line-clamp-2 mb-3">{{ $product->description }}</p>
                    @else
                        <div class="mb-3"></div>
                    @endif

                    {{-- Prices --}}
                    <div class="space-y-1 mb-4">
                        <div class="flex items-center justify-between">
                            <span class="text-[0.65rem] font-semibold text-caramel uppercase tracking-wider">{{ $product->category->isCoffee() && $product->hasLitePrice() ? 'Base' : 'Harga' }}</span>
                            <span class="text-sm font-bold text-espresso">{{ $product->formatted_price }}</span>
                        </div>
                        @if ($product->category->isCoffee() && $product->hasLitePrice())
                            <div class="flex items-center justify-between">
                                <span class="text-[0.65rem] font-semibold text-caramel uppercase tracking-wider">Lite</span>
                                <span class="text-sm font-bold text-espresso">{{ $product->formatted_price_lite }}</span>
                            </div>
                        @endif
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center gap-2 pt-3 border-t border-latte/30">
                        <a href="{{ route('pemilik.products.edit', $product) }}" class="flex-1 text-center text-xs font-semibold py-2 rounded-lg bg-espresso/5 text-espresso hover:bg-espresso/15 transition">Edit</a>
                        <form method="POST" action="{{ route('pemilik.products.toggle-status', $product) }}" class="flex-1">
                            @csrf @method('PATCH')
                            <button type="submit" class="w-full text-xs font-semibold py-2 rounded-lg transition {{ $product->is_active ? 'bg-red-50 text-red-600 hover:bg-red-100' : 'bg-green-50 text-green-600 hover:bg-green-100' }}">
                                {{ $product->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('pemilik.products.destroy', $product) }}" onsubmit="return confirm('Yakin ingin menghapus produk ini?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="p-2 rounded-lg text-red-400 hover:bg-red-50 hover:text-red-600 transition" title="Hapus">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full">
                <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-12">
                    <div class="flex flex-col items-center">
                        <div class="w-16 h-16 bg-latte/30 rounded-2xl flex items-center justify-center mb-4">
                            <svg class="w-8 h-8 text-caramel" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
                        </div>
                        <p class="text-sm text-caramel-dark font-medium mb-1">Belum ada produk</p>
                        <p class="text-xs text-caramel mb-4">Mulai tambahkan menu kopi, makanan, dan minuman.</p>
                        <a href="{{ route('pemilik.products.create') }}" class="inline-flex items-center gap-2 bg-espresso hover:bg-espresso-light text-cream text-sm font-medium px-5 py-2.5 rounded-xl transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                            Tambah Produk Pertama
                        </a>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    @if ($products->hasPages())
        <div class="mt-6">{{ $products->links('components.pagination') }}</div>
    @endif
@endsection
