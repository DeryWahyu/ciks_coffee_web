@extends('layouts.karyawan')

@section('title', 'Point of Sales')
@section('page-title', 'Point of Sales')
@section('page-description', 'Kelola pesanan pelanggan')

@section('content')
<div class="flex flex-col lg:flex-row gap-6 pb-20 lg:pb-0">
    {{-- Left: Product Menu --}}
    <div class="flex-1">
        {{-- Search & Category Filter --}}
        <div class="mb-5 flex flex-wrap items-center gap-3">
            <div class="relative flex-1 min-w-0">
                <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-caramel" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                <input type="text" id="pos-search" placeholder="Cari menu..." class="w-full pl-10 pr-4 py-2.5 text-sm bg-white border border-latte/60 rounded-xl focus:ring-2 focus:ring-caramel/30 focus:border-caramel outline-none transition">
            </div>
        </div>

        {{-- Category Tabs --}}
        <div class="flex items-center gap-2 mb-5 overflow-x-auto pb-1">
            <button onclick="filterCategory(null)" class="cat-tab active shrink-0 px-4 py-2 text-xs font-semibold rounded-xl border transition-all duration-200" data-cat="all">
                Semua
            </button>
            @foreach ($categories as $cat)
                <button onclick="filterCategory({{ $cat->id }})" class="cat-tab shrink-0 px-4 py-2 text-xs font-semibold rounded-xl border transition-all duration-200" data-cat="{{ $cat->id }}">
                    {{ $cat->name }}
                </button>
            @endforeach
        </div>

        {{-- Product Grid --}}
        <div id="product-grid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-4">
            @forelse ($products as $product)
                <div class="product-card bg-white rounded-2xl shadow-sm border border-latte/50 overflow-hidden cursor-pointer group hover:shadow-md hover:border-caramel/40 transition-all duration-300"
                     data-id="{{ $product->id }}"
                     data-name="{{ $product->name }}"
                     data-price="{{ $product->price }}"
                     data-price-lite="{{ $product->price_lite }}"
                     data-image="{{ $product->image ? '/storage/' . $product->image : '' }}"
                     data-category="{{ $product->category_id }}"
                     data-is-coffee="{{ $product->category->isCoffee() ? '1' : '0' }}"
                     onclick="showProductDetail(this)">
                    <div class="relative aspect-square bg-latte/20 overflow-hidden">
                        @if ($product->image)
                            <img src="{{ '/storage/' . $product->image }}" alt="{{ $product->name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        @else
                            <div class="w-full h-full flex items-center justify-center">
                                <svg class="w-10 h-10 text-caramel/30" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5a2.25 2.25 0 002.25-2.25V5.25a2.25 2.25 0 00-2.25-2.25H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21z"/></svg>
                            </div>
                        @endif
                        <div class="absolute top-2 right-2">
                            <span class="px-2 py-0.5 rounded-full text-[0.6rem] font-bold uppercase tracking-wider bg-espresso/80 text-cream backdrop-blur-sm">{{ $product->category->name }}</span>
                        </div>
                    </div>
                    <div class="p-2 sm:p-3">
                        <h3 class="text-[0.65rem] sm:text-xs font-bold text-espresso truncate mb-1">{{ $product->name }}</h3>
                        <p class="mb-1.5 text-[0.58rem] leading-[0.875rem] text-caramel-dark line-clamp-2 sm:text-[0.65rem] sm:leading-4">{{ $product->description ?: 'Deskripsi belum tersedia.' }}</p>
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-0.5">
                            <span class="text-[0.65rem] sm:text-xs font-bold text-espresso">{{ $product->formatted_price }}</span>
                            @if ($product->category->isCoffee() && $product->hasLitePrice())
                                <span class="text-[0.55rem] sm:text-[0.6rem] text-caramel font-medium">Lite: {{ $product->formatted_price_lite }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full">
                    <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-12 text-center">
                        <div class="w-16 h-16 bg-latte/30 rounded-2xl flex items-center justify-center mb-4 mx-auto">
                            <svg class="w-8 h-8 text-caramel" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
                        </div>
                        <p class="text-sm text-caramel-dark font-medium">Belum ada produk aktif</p>
                        <p class="text-xs text-caramel mt-1">Hubungi pemilik untuk menambahkan produk.</p>
                    </div>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Right: Order Cart --}}
    <div id="cart-panel" class="fixed inset-0 z-[100] hidden lg:relative lg:!block lg:inset-auto lg:z-auto w-full lg:w-96 shrink-0">
        <div class="absolute inset-0 bg-black/40 lg:hidden" onclick="toggleMobileCart()"></div>
        <div class="absolute bottom-0 left-0 right-0 lg:relative bg-white rounded-t-2xl lg:rounded-2xl shadow-sm border border-latte/50 lg:sticky lg:top-20 overflow-hidden max-h-[70vh] lg:max-h-none flex flex-col" style="padding-bottom: env(safe-area-inset-bottom, 0px);">
            {{-- Cart Header --}}
            <div class="px-4 sm:px-5 py-3 sm:py-4 border-b border-latte/40 bg-espresso/5 shrink-0">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold text-espresso flex items-center gap-2">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121 0 2.09-.773 2.21-1.886L21 5.25H6.228"/></svg>
                        Pesanan
                    </h3>
                    <div class="flex items-center gap-3">
                        <button onclick="clearCart()" class="text-xs text-red-400 hover:text-red-600 font-medium transition" id="btn-clear-cart">Hapus Semua</button>
                        <button onclick="toggleMobileCart()" class="lg:hidden p-1 rounded-lg hover:bg-latte/30 text-caramel transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Cart Items --}}
            <div id="cart-items" class="max-h-[30vh] lg:max-h-[40vh] overflow-y-auto px-4 sm:px-5 py-2 sm:py-3 flex-1">
                <div id="cart-empty" class="text-center py-6 sm:py-8">
                    <svg class="w-10 h-10 sm:w-12 sm:h-12 text-caramel/25 mx-auto mb-2 sm:mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121 0 2.09-.773 2.21-1.886L21 5.25H6.228"/></svg>
                    <p class="text-xs text-caramel">Belum ada item dipilih</p>
                    <p class="text-[0.6rem] text-caramel/60 mt-1">Klik produk untuk menambahkan</p>
                </div>
            </div>

            {{-- Cart Footer --}}
            <div class="border-t border-latte/40 px-4 sm:px-5 py-3 sm:py-4 space-y-2 sm:space-y-3 shrink-0">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-semibold text-espresso">Total</span>
                    <span id="cart-total" class="text-lg font-bold text-espresso">Rp 0</span>
                </div>
                <button onclick="processOrder()" id="btn-process-order" disabled class="w-full bg-espresso hover:bg-espresso-light disabled:opacity-40 disabled:cursor-not-allowed text-cream text-sm font-semibold py-2.5 sm:py-3 rounded-xl transition-all duration-200 hover:shadow-md flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Proses Pesanan
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('modals')
{{-- Mobile Cart Toggle --}}
<button onclick="toggleMobileCart()" id="mobile-cart-btn" class="lg:hidden fixed z-[110] w-14 h-14 bg-espresso text-cream rounded-full shadow-xl flex items-center justify-center hover:bg-espresso-light transition-all" style="right:20px;bottom:20px;">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121 0 2.09-.773 2.21-1.886L21 5.25H6.228"/></svg>
    <span id="mobile-cart-badge" class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-[0.6rem] font-bold rounded-full items-center justify-center hidden">0</span>
</button>
{{-- Product Detail Modal --}}
<div id="product-modal" class="fixed inset-0 z-[120] hidden">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closeModal()"></div>
    <div class="absolute inset-0 overflow-y-auto flex items-center justify-center p-3 sm:p-4 py-12 sm:py-16">
        <div class="bg-white rounded-2xl shadow-2xl border border-latte/50 w-full max-w-lg animate-fade-in overflow-hidden my-auto">
            <div class="flex items-start gap-3 sm:gap-4 p-4 sm:p-6 border-b border-latte/30">
                <div id="modal-image-wrap" class="w-20 h-20 rounded-xl overflow-hidden bg-latte/20 shrink-0">
                    <img id="modal-image" src="" alt="" class="w-full h-full object-cover hidden">
                    <div id="modal-image-placeholder" class="w-full h-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-caramel/30" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159"/></svg>
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <h3 id="modal-name" class="text-base font-bold text-espresso"></h3>
                    <p id="modal-category" class="text-xs text-caramel font-medium mt-0.5"></p>
                    <p id="modal-desc" class="text-xs text-caramel-dark mt-1 line-clamp-2"></p>
                </div>
                <button onclick="closeModal()" class="p-1 rounded-lg hover:bg-latte/30 text-caramel transition shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="p-4 sm:p-6 space-y-4 sm:space-y-5">
                {{-- Variant Selection (Coffee only) --}}
                <div id="modal-variant-section" class="hidden">
                    <label class="block text-xs font-bold text-espresso uppercase tracking-wider mb-2">Pilih Varian</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" name="variant" value="base" class="peer hidden" checked>
                            <div class="peer-checked:border-espresso peer-checked:bg-espresso/5 border-2 border-latte/60 rounded-xl p-3 text-center transition-all">
                                <p class="text-sm font-bold text-espresso">Base</p>
                                <p id="modal-price-base" class="text-xs text-caramel mt-0.5"></p>
                            </div>
                        </label>
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" name="variant" value="lite" class="peer hidden">
                            <div class="peer-checked:border-espresso peer-checked:bg-espresso/5 border-2 border-latte/60 rounded-xl p-3 text-center transition-all">
                                <p class="text-sm font-bold text-espresso">Lite</p>
                                <p id="modal-price-lite" class="text-xs text-caramel mt-0.5"></p>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Price for non-coffee --}}
                <div id="modal-price-section" class="hidden">
                    <div class="flex items-center justify-between bg-cream/50 rounded-xl p-3">
                        <span class="text-xs font-semibold text-caramel uppercase tracking-wider">Harga</span>
                        <span id="modal-price-single" class="text-base font-bold text-espresso"></span>
                    </div>
                </div>

                {{-- Ingredients --}}
                <div>
                    <label class="block text-xs font-bold text-espresso uppercase tracking-wider mb-2">Komposisi Bahan Baku</label>
                    <div id="modal-ingredients" class="space-y-1.5 max-h-32 overflow-y-auto"></div>
                    <div id="modal-no-ingredients" class="hidden text-xs text-caramel italic py-2">Belum ada komposisi bahan baku.</div>
                </div>

                {{-- Quantity --}}
                <div>
                    <label class="block text-xs font-bold text-espresso uppercase tracking-wider mb-2">Jumlah</label>
                    <div class="flex items-center gap-3">
                        <button type="button" onclick="changeQty(-1)" class="w-10 h-10 rounded-xl bg-latte/30 hover:bg-latte/50 text-espresso font-bold text-lg transition flex items-center justify-center">−</button>
                        <input type="number" id="modal-qty" value="1" min="1" max="99" class="w-16 text-center text-sm font-bold text-espresso border border-latte/60 rounded-xl py-2 focus:ring-2 focus:ring-caramel/30 outline-none">
                        <button type="button" onclick="changeQty(1)" class="w-10 h-10 rounded-xl bg-latte/30 hover:bg-latte/50 text-espresso font-bold text-lg transition flex items-center justify-center">+</button>
                    </div>
                </div>
            </div>

            <div class="px-4 sm:px-6 pb-4 sm:pb-6">
                <button onclick="addToCartFromModal()" class="w-full bg-espresso hover:bg-espresso-light text-cream text-sm font-semibold py-3 rounded-xl transition-all duration-200 hover:shadow-md flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Tambahkan ke Pesanan
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Checkout Modal --}}
<div id="checkout-modal" class="fixed inset-0 z-[120] hidden">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closeCheckout()"></div>
    <div class="absolute inset-0 overflow-y-auto flex items-center justify-center p-3 sm:p-4 py-12 sm:py-16">
        <div class="bg-white rounded-2xl shadow-2xl border border-latte/50 w-full max-w-md animate-fade-in overflow-hidden my-auto">
            <div class="px-6 py-4 border-b border-latte/30 bg-espresso/5 flex items-center justify-between">
                <h3 class="text-sm font-bold text-espresso">Proses Pembayaran</h3>
                <button onclick="closeCheckout()" class="p-1 rounded-lg hover:bg-latte/30 text-caramel transition"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <div class="p-4 sm:p-6 space-y-4">
                <div>
                    <label class="block text-xs font-bold text-espresso uppercase tracking-wider mb-1.5">Nama Pelanggan <span class="text-red-500">*</span></label>
                    <input type="text" id="checkout-customer" placeholder="Masukkan nama pelanggan" class="w-full px-4 py-2.5 text-sm bg-white border border-latte/60 rounded-xl focus:ring-2 focus:ring-caramel/30 focus:border-caramel outline-none transition">
                </div>
                <div>
                    <label class="block text-xs font-bold text-espresso uppercase tracking-wider mb-2">Metode Pembayaran</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="flex-1 cursor-pointer"><input type="radio" name="payment" value="cash" class="peer hidden" checked><div class="peer-checked:border-espresso peer-checked:bg-espresso/5 border-2 border-latte/60 rounded-xl p-3 text-center transition-all"><svg class="w-6 h-6 mx-auto mb-1 text-espresso" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"/></svg><p class="text-xs font-bold text-espresso">Cash</p></div></label>
                        <label class="flex-1 cursor-pointer"><input type="radio" name="payment" value="qris" class="peer hidden"><div class="peer-checked:border-espresso peer-checked:bg-espresso/5 border-2 border-latte/60 rounded-xl p-3 text-center transition-all"><svg class="w-6 h-6 mx-auto mb-1 text-espresso" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z"/><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 14.625v5.25h5.25v-3m0 0h.375a.375.375 0 00.375-.375v-.375"/></svg><p class="text-xs font-bold text-espresso">QRIS</p></div></label>
                    </div>
                </div>
                <div id="cash-section">
                    <label class="block text-xs font-bold text-espresso uppercase tracking-wider mb-1.5">Uang Diterima</label>
                    <div class="relative"><span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm text-caramel font-medium">Rp</span><input type="number" id="checkout-cash" min="0" step="1000" class="w-full pl-12 pr-4 py-2.5 text-sm bg-white border border-latte/60 rounded-xl focus:ring-2 focus:ring-caramel/30 focus:border-caramel outline-none transition" oninput="calcChange()"></div>
                    <p id="checkout-change" class="text-xs text-green-600 font-medium mt-1.5 hidden"></p>
                </div>
                <div id="qris-section" class="hidden text-center">
                    <img id="pos-qris-image" class="hidden max-h-64 mx-auto mb-3 rounded-xl" alt="QRIS Ciks Coffee">
                    <div class="py-6 bg-latte/20 rounded-xl">
                        <svg class="w-12 h-12 text-caramel/40 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z"/><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 14.625v5.25h5.25v-3m0 0h.375a.375.375 0 00.375-.375v-.375"/></svg>
                        <p class="text-xs text-caramel font-medium">Pembayaran QRIS</p>
                        <p class="text-[0.6rem] text-caramel/60 mt-1">Kasir wajib memastikan pembayaran telah diterima sebelum memproses pesanan</p>
                    </div>
                </div>
                <div class="flex items-center justify-between bg-cream/50 rounded-xl p-3">
                    <span class="text-xs font-semibold text-caramel uppercase">Total</span>
                    <span id="checkout-total" class="text-lg font-bold text-espresso"></span>
                </div>
            </div>
            <div class="px-4 sm:px-6 pb-4 sm:pb-6"><button onclick="submitCheckout()" id="btn-submit-checkout" class="w-full bg-espresso hover:bg-espresso-light text-cream text-sm font-semibold py-3 rounded-xl transition-all duration-200 hover:shadow-md flex items-center justify-center gap-2"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>Bayar Sekarang</button></div>
        </div>
    </div>
</div>

{{-- Receipt Modal --}}
<div id="receipt-modal" class="fixed inset-0 z-[120] hidden">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>
    <div class="absolute inset-0 overflow-y-auto flex items-center justify-center p-3 sm:p-4 py-12 sm:py-16">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm animate-fade-in my-auto">
            <div id="receipt-content" class="p-4 sm:p-6">
                <div class="text-center border-b border-dashed border-latte/60 pb-4 mb-4">
                    <x-brand-logo style="display:block;width:40px;height:40px;margin:0 auto 8px;" />
                    <h3 class="text-base font-bold text-espresso" style="font-family:'Playfair Display',serif">Ciks Coffee</h3>
                    <p class="text-[0.6rem] text-caramel mt-1">Terima kasih atas kunjungan Anda</p>
                </div>
                <div class="space-y-1.5 text-xs mb-4">
                    <div class="flex justify-between"><span class="text-caramel">No. Pesanan</span><span id="rcpt-number" class="font-bold text-espresso"></span></div>
                    <div class="flex justify-between"><span class="text-caramel">Pelanggan</span><span id="rcpt-customer" class="font-medium text-espresso"></span></div>
                    <div class="flex justify-between"><span class="text-caramel">Kasir</span><span id="rcpt-cashier" class="text-espresso"></span></div>
                    <div class="flex justify-between"><span class="text-caramel">Waktu</span><span id="rcpt-time" class="text-espresso"></span></div>
                    <div class="flex justify-between"><span class="text-caramel">Pembayaran</span><span id="rcpt-payment" class="font-medium text-espresso uppercase"></span></div>
                </div>
                <div class="border-t border-dashed border-latte/60 pt-3 mb-3"><div id="rcpt-items" class="space-y-2"></div></div>
                <div class="border-t border-dashed border-latte/60 pt-3 space-y-1.5">
                    <div class="flex justify-between text-sm"><span class="font-bold text-espresso">Total</span><span id="rcpt-total" class="font-bold text-espresso"></span></div>
                    <div id="rcpt-cash-row" class="flex justify-between text-xs hidden"><span class="text-caramel">Tunai</span><span id="rcpt-cash" class="text-espresso"></span></div>
                    <div id="rcpt-change-row" class="flex justify-between text-xs hidden"><span class="text-caramel">Kembalian</span><span id="rcpt-change" class="font-medium text-green-600"></span></div>
                </div>
                <div class="border-t border-dashed border-latte/60 mt-4 pt-3"><div class="flex justify-between text-xs"><span class="text-caramel">Status</span><span id="rcpt-status" class="font-bold"></span></div></div>
            </div>
            <div class="px-4 sm:px-6 pb-4 sm:pb-6 flex flex-col sm:flex-row gap-3">
                <button onclick="printReceipt()" class="flex-1 bg-espresso hover:bg-espresso-light text-cream text-sm font-semibold py-3 rounded-xl transition-all flex items-center justify-center gap-2"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z"/></svg>Cetak Struk</button>
                <button onclick="closeReceipt()" class="px-4 py-3 text-sm font-medium text-caramel hover:text-espresso rounded-xl border border-latte/60 hover:border-caramel transition">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endpush

@push('styles')
<style>
    .cat-tab { border-color: rgba(215,204,200,0.5); color: #A1887F; background: white; }
    .cat-tab:hover { border-color: #A1887F; color: #3E2723; }
    .cat-tab.active { border-color: #3E2723; background-color: #3E2723; color: #F5F5DC; }
    .cart-item { animation: slideIn 0.25s ease-out; }
    @keyframes slideIn { from { opacity:0; transform:translateX(12px); } to { opacity:1; transform:translateX(0); } }
</style>
@endpush

@push('scripts')
<script>
    // === State ===
    let cart = [];
    let currentProduct = null;
    let checkoutBusy = false;
    let preflightBusy = false;
    let detailRequest = 0;
    let selectedCategory = null;
    const checkoutUrl = @json(route('karyawan.pos.checkout'));
    const paymentStorageKey = @json('ciks:pos-payment:'.auth()->id());
    let pendingPayment = null;
    try { pendingPayment = JSON.parse(sessionStorage.getItem(paymentStorageKey) || 'null'); } catch {}
    if (pendingPayment?.cart?.length && pendingPayment?.payload?.idempotency_key) cart = pendingPayment.cart;
    else pendingPayment = null;
    const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, character => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[character]));
    const units = value => Math.round(Number(value) * 100);
    const cartTotal = () => cart.reduce((sum, item) => sum + units(item.price) * item.qty, 0) / 100;
    const quantity = value => Math.max(1, Math.min(2147483647, parseInt(value, 10) || 1));
    function editableCart() {
        if (checkoutBusy || preflightBusy) return false;
        if (pendingPayment) {
            window.CiksAlert.notify('Periksa kembali hasil pembayaran sebelumnya sebelum mengubah pesanan.', 'warning', 'Pembayaran belum terkonfirmasi');
            return false;
        }
        return true;
    }
    function persistPayment(payment) {
        pendingPayment = payment;
        try {
            if (payment) sessionStorage.setItem(paymentStorageKey, JSON.stringify(payment));
            else sessionStorage.removeItem(paymentStorageKey);
        } catch {}
    }
    function paymentKey() {
        if (crypto.randomUUID) return crypto.randomUUID();
        const bytes = crypto.getRandomValues(new Uint8Array(16));
        bytes[6] = (bytes[6] & 15) | 64; bytes[8] = (bytes[8] & 63) | 128;
        const hex = [...bytes].map(value => value.toString(16).padStart(2, '0')).join('');
        return `${hex.slice(0,8)}-${hex.slice(8,12)}-${hex.slice(12,16)}-${hex.slice(16,20)}-${hex.slice(20)}`;
    }
    function recipeFor(product, variant) {
        return product.ingredients?.[product.is_coffee ? variant : 'default'] || [];
    }
    function stockError(items) {
        const totals = new Map();
        for (const item of items) {
            if (!item.ingredients?.length) return `Resep ${item.name} belum tersedia.`;
            for (const ingredient of item.ingredients) {
                const entry = totals.get(ingredient.id) || { required: 0, available: units(ingredient.stok), ingredient };
                entry.required += units(ingredient.quantity) * item.qty;
                entry.available = Math.min(entry.available, units(ingredient.stok));
                totals.set(ingredient.id, entry);
            }
        }
        for (const {required, available, ingredient} of totals.values()) {
            if (required > available) return `Stok bahan "${ingredient.nama_bahan}" tidak cukup untuk seluruh keranjang. Dibutuhkan ${required / 100} ${ingredient.satuan}, tersedia ${available / 100} ${ingredient.satuan}.`;
        }
        return null;
    }
    async function refreshCart() {
        const products = new Map();
        await Promise.all([...new Set(cart.map(item => item.id))].map(async id => {
            const response = await fetch(`/karyawan/pos/${id}/detail`, {headers: {'Accept':'application/json'}});
            if (!response.ok) throw new Error('Salah satu produk sudah tidak tersedia. Periksa kembali keranjang.');
            products.set(id, await response.json());
        }));
        cart = cart.map(item => {
            const product = products.get(item.id);
            if (item.variant === 'lite' && product.price_lite == null) throw new Error('Varian Lite sudah tidak tersedia. Periksa kembali keranjang.');
            if (Boolean(item.variant) !== Boolean(product.is_coffee)) throw new Error('Kategori produk berubah. Pilih ulang produk di keranjang.');
            return {...item, name: product.name, price: Number(item.variant === 'lite' ? product.price_lite : product.price), ingredients: recipeFor(product, item.variant)};
        });
        renderCart();
        const error = stockError(cart);
        if (error) throw new Error(error);
    }

    // === Mobile Cart Toggle ===
    function toggleMobileCart() {
        const panel = document.getElementById('cart-panel');
        panel.classList.toggle('hidden');
    }

    // === Category Filter ===
    function applyProductFilters() {
        const search = document.getElementById('pos-search').value.toLowerCase();
        document.querySelectorAll('.product-card').forEach(card => {
            const categoryMatches = selectedCategory === null || card.dataset.category == selectedCategory;
            card.style.display = categoryMatches && card.dataset.name.toLowerCase().includes(search) ? '' : 'none';
        });
    }
    function filterCategory(catId) {
        selectedCategory = catId;
        document.querySelectorAll('.cat-tab').forEach(tab => tab.classList.remove('active'));
        document.querySelector(`[data-cat="${catId === null ? 'all' : catId}"]`)?.classList.add('active');
        applyProductFilters();
    }
    document.getElementById('pos-search').addEventListener('input', applyProductFilters);

    // === Product Detail Modal ===
    function showProductDetail(el) {
        if (!editableCart()) return;
        const request = ++detailRequest;
        const id = el.dataset.id;
        fetch(`/karyawan/pos/${id}/detail`)
            .then(async response => {
                if (!response.ok) throw new Error('Produk sudah tidak tersedia. Muat ulang daftar produk.');
                return response.json();
            })
            .then(data => {
                if (request !== detailRequest) return;
                currentProduct = data;
                document.getElementById('modal-name').textContent = data.name;
                document.getElementById('modal-category').textContent = data.category;
                document.getElementById('modal-desc').textContent = data.description || '';
                document.getElementById('modal-qty').value = 1;

                const img = document.getElementById('modal-image');
                const placeholder = document.getElementById('modal-image-placeholder');
                if (data.image) { img.src = data.image; img.classList.remove('hidden'); placeholder.classList.add('hidden'); }
                else { img.classList.add('hidden'); placeholder.classList.remove('hidden'); }

                const variantSec = document.getElementById('modal-variant-section');
                const priceSec = document.getElementById('modal-price-section');
                if (data.is_coffee) {
                    // Coffee always uses an explicit recipe variant. Base-only
                    // products keep the selector hidden but still submit "base".
                    document.querySelector('input[name="variant"][value="base"]').checked = true;

                    if (data.price_lite != null) {
                        variantSec.classList.remove('hidden');
                        priceSec.classList.add('hidden');
                        document.getElementById('modal-price-base').textContent = data.formatted_price;
                        document.getElementById('modal-price-lite').textContent = data.formatted_price_lite;
                    } else {
                        variantSec.classList.add('hidden');
                        priceSec.classList.remove('hidden');
                        document.getElementById('modal-price-single').textContent = data.formatted_price;
                    }
                } else {
                    variantSec.classList.add('hidden');
                    priceSec.classList.remove('hidden');
                    document.getElementById('modal-price-single').textContent = data.formatted_price;
                }

                if (data.is_coffee && data.price_lite != null) {
                    const canMake = variant => {
                        const ingredients = recipeFor(data, variant);
                        return ingredients.length > 0 && ingredients.every(ingredient => units(ingredient.stok) >= units(ingredient.quantity));
                    };
                    for (const variant of ['base', 'lite']) {
                        const input = document.querySelector(`input[name="variant"][value="${variant}"]`);
                        input.disabled = !canMake(variant);
                        input.closest('label').classList.toggle('opacity-40', input.disabled);
                    }
                    if (!canMake('base') && canMake('lite')) document.querySelector('input[name="variant"][value="lite"]').checked = true;
                }
                renderModalIngredients(data);
                document.getElementById('product-modal').classList.remove('hidden');
            }).catch(error => window.CiksAlert.notify(error.message, 'error', 'Produk tidak dapat dibuka'));
    }

    function renderModalIngredients(data) {
        const container = document.getElementById('modal-ingredients');
        const noIng = document.getElementById('modal-no-ingredients');
        container.innerHTML = '';

        let items = [];
        if (data.is_coffee) {
            const variant = document.querySelector('input[name="variant"]:checked')?.value || 'base';
            items = data.ingredients[variant] || [];
        } else {
            items = data.ingredients['default'] || [];
        }

        if (items.length === 0) {
            noIng.classList.remove('hidden');
            container.classList.add('hidden');
            return;
        }
        noIng.classList.add('hidden');
        container.classList.remove('hidden');

        items.forEach(ing => {
            const stockOk = units(ing.stok) >= units(ing.quantity);
            const row = document.createElement('div');
            row.className = 'flex items-center justify-between py-1.5 px-3 rounded-lg ' + (stockOk ? 'bg-green-50/50' : 'bg-red-50/50');
            row.innerHTML = `
                <span class="text-xs text-espresso">${escapeHtml(ing.nama_bahan)}</span>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-medium ${stockOk ? 'text-green-600' : 'text-red-500'}">${ing.quantity} ${escapeHtml(ing.satuan)}</span>
                    <span class="text-[0.6rem] ${stockOk ? 'text-green-500' : 'text-red-400'}">(stok: ${ing.stok})</span>
                </div>
            `;
            container.appendChild(row);
        });
    }

    // Update ingredients when variant changes
    document.querySelectorAll('input[name="variant"]').forEach(r => {
        r.addEventListener('change', () => { if (currentProduct) renderModalIngredients(currentProduct); });
    });

    function closeModal() { document.getElementById('product-modal').classList.add('hidden'); currentProduct = null; detailRequest += 1; }
    function changeQty(delta) {
        const inp = document.getElementById('modal-qty');
        inp.value = Math.min(99, quantity(quantity(inp.value) + delta));
    }

    // === Cart ===
    function getActiveIngredients(variant) {
        if (!currentProduct || !currentProduct.ingredients) return [];
        if (currentProduct.is_coffee) {
            return currentProduct.ingredients[variant] || [];
        }
        return currentProduct.ingredients['default'] || [];
    }

    // Validasi stok bahan baku: cek kecukupan stok untuk kuantitas yang diminta,
    // termasuk qty item+varian yang sama yang sudah ada di keranjang (akumulatif).
    function validateIngredientStock(variant, requestedQty) {
        const key = `${currentProduct.id}-${variant || 'default'}`;
        const candidate = cart.map(item => ({...item}));
        const existing = candidate.find(item => item.key === key);
        if (existing) {
            existing.qty += requestedQty;
            existing.ingredients = getActiveIngredients(variant);
        } else candidate.push({key, name: currentProduct.name, qty: requestedQty, ingredients: getActiveIngredients(variant)});
        return stockError(candidate);
    }

    function addToCartFromModal() {
        if (!currentProduct || !editableCart()) return;
        const qty = Math.min(99, quantity(document.getElementById('modal-qty').value));
        let variant = null, price = parseFloat(currentProduct.price);

        if (currentProduct.is_coffee) {
            variant = currentProduct.price_lite != null
                ? (document.querySelector('input[name="variant"]:checked')?.value || 'base')
                : 'base';
            price = variant === 'lite' ? parseFloat(currentProduct.price_lite) : parseFloat(currentProduct.price);
        }

        // Validasi stok bahan baku SEBELUM menambahkan ke keranjang
        const stockError = validateIngredientStock(variant, qty);
        if (stockError) {
            window.CiksAlert.notify(stockError, 'warning', 'Stok bahan belum cukup');
            return; // tahan modal tetap terbuka agar karyawan bisa sesuaikan jumlah
        }

        const cartKey = `${currentProduct.id}-${variant || 'default'}`;
        const existing = cart.find(i => i.key === cartKey);
        if (existing) { existing.qty += qty; existing.ingredients = getActiveIngredients(variant); existing.price = price; }
        else {
            cart.push({
                key: cartKey, id: currentProduct.id, name: currentProduct.name,
                variant: variant, price: price, qty: qty, ingredients: getActiveIngredients(variant)
            });
        }
        renderCart();
        closeModal();
        // Auto-open mobile cart when item is added
        if (window.innerWidth < 1024) {
            document.getElementById('cart-panel').classList.remove('hidden');
        }
    }

    function removeFromCart(key) { if (!editableCart()) return; cart = cart.filter(item => item.key !== key); renderCart(); }
    function updateCartQty(key, delta) {
        if (!editableCart()) return;
        const candidate = cart.map(item => ({...item, qty: item.key === key ? quantity(item.qty + delta) : item.qty}));
        const error = stockError(candidate);
        if (error) { window.CiksAlert.notify(error, 'warning', 'Stok bahan belum cukup'); return; }
        cart = candidate; renderCart();
    }
    function clearCart() { if (!editableCart()) return; cart = []; renderCart(); }

    function renderCart() {
        const container = document.getElementById('cart-items');
        const totalEl = document.getElementById('cart-total');
        const processBtn = document.getElementById('btn-process-order');
        const badge = document.getElementById('mobile-cart-badge');

        const emptyHTML = `<div class="text-center py-8">
            <svg class="w-12 h-12 text-caramel/25 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121 0 2.09-.773 2.21-1.886L21 5.25H6.228"/></svg>
            <p class="text-xs text-caramel">Belum ada item dipilih</p>
            <p class="text-[0.6rem] text-caramel/60 mt-1">Klik produk untuk menambahkan</p>
        </div>`;

        if (cart.length === 0) {
            container.innerHTML = emptyHTML;
            totalEl.textContent = 'Rp 0';
            processBtn.disabled = true;
            if (badge) { badge.textContent = '0'; badge.style.display = 'none'; }
            return;
        }

        let html = '';
        let total = 0;
        cart.forEach(item => {
            const subtotal = item.price * item.qty;
            total += subtotal;
            const variantLabel = item.variant ? ` (${item.variant.charAt(0).toUpperCase() + item.variant.slice(1)})` : '';
            html += `
                <div class="cart-item flex items-start gap-3 py-3 border-b border-latte/20 last:border-0">
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-bold text-espresso truncate">${escapeHtml(item.name)}${variantLabel}</p>
                        <p class="text-[0.6rem] text-caramel mt-0.5">Rp ${item.price.toLocaleString('id-ID')} × ${item.qty}</p>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <button onclick="updateCartQty('${item.key}', -1)" class="w-6 h-6 rounded-md bg-latte/30 hover:bg-latte/50 text-espresso text-xs font-bold flex items-center justify-center transition">−</button>
                        <span class="text-xs font-bold text-espresso w-5 text-center">${item.qty}</span>
                        <button onclick="updateCartQty('${item.key}', 1)" class="w-6 h-6 rounded-md bg-latte/30 hover:bg-latte/50 text-espresso text-xs font-bold flex items-center justify-center transition">+</button>
                    </div>
                    <div class="text-right shrink-0">
                        <p class="text-xs font-bold text-espresso">Rp ${subtotal.toLocaleString('id-ID')}</p>
                        <button onclick="removeFromCart('${item.key}')" class="text-[0.6rem] text-red-400 hover:text-red-600 transition mt-0.5">Hapus</button>
                    </div>
                </div>
            `;
        });

        container.innerHTML = html;
        totalEl.textContent = 'Rp ' + total.toLocaleString('id-ID');
        processBtn.disabled = false;

        const totalQty = cart.reduce((s, i) => s + i.qty, 0);
        if (badge) {
            badge.textContent = totalQty;
            badge.style.display = totalQty > 0 ? 'flex' : 'none';
        }
    }

    async function processOrder() {
        if (cart.length === 0 || checkoutBusy || preflightBusy) return;
        preflightBusy = true;
        const processButton = document.getElementById('btn-process-order');
        processButton.disabled = true;
        try {
            if (!pendingPayment) await refreshCart();
            const previous = pendingPayment?.payload;
            document.getElementById('checkout-total').textContent = 'Rp ' + Number(previous?.expected_total ?? cartTotal()).toLocaleString('id-ID');
            document.getElementById('checkout-customer').value = previous?.customer_name ?? '';
            document.getElementById('checkout-cash').value = previous?.cash_received ?? '';
            document.getElementById('checkout-change').classList.add('hidden');
            document.querySelector(`input[name="payment"][value="${previous?.payment_method ?? 'cash'}"]`).checked = true;
            document.querySelectorAll('#checkout-customer, #checkout-cash, input[name="payment"]').forEach(input => input.disabled = Boolean(previous));
            togglePaymentMethod();
            if (window.innerWidth < 1024) document.getElementById('cart-panel').classList.add('hidden');
            document.getElementById('checkout-modal').classList.remove('hidden');
        } catch (error) {
            window.CiksAlert.notify(error.message, 'warning', 'Periksa keranjang');
        } finally {
            preflightBusy = false; processButton.disabled = cart.length === 0;
        }
    }
    function closeCheckout(force = false) { if (!checkoutBusy || force) document.getElementById('checkout-modal').classList.add('hidden'); }
    document.querySelectorAll('input[name="payment"]').forEach(radio => radio.addEventListener('change', togglePaymentMethod));
    function togglePaymentMethod() {
        const method = document.querySelector('input[name="payment"]:checked').value;
        document.getElementById('cash-section').style.display = method === 'cash' ? '' : 'none';
        document.getElementById('qris-section').style.display = method === 'qris' ? '' : 'none';
        if (method === 'qris') {
            fetch(@json(route('karyawan.pos.payment-settings')), {headers: {'Accept':'application/json'}, cache: 'no-store'})
                .then(response => response.ok ? response.json() : {image_url: null})
                .then(data => {
                    const image = document.getElementById('pos-qris-image');
                    image.classList.toggle('hidden', !data.image_url);
                    if (data.image_url) image.src = data.image_url;
                }).catch(() => document.getElementById('pos-qris-image').classList.add('hidden'));
        }
    }
    function calcChange() {
        const change = units(document.getElementById('checkout-cash').value || 0) - units(cartTotal());
        const changeElement = document.getElementById('checkout-change');
        changeElement.classList.toggle('hidden', change < 0);
        if (change >= 0) changeElement.textContent = 'Kembalian: Rp ' + (change / 100).toLocaleString('id-ID');
    }
    async function submitCheckout() {
        if (checkoutBusy) return;
        checkoutBusy = true;
        const button = document.getElementById('btn-submit-checkout');
        const label = button.innerHTML;
        button.disabled = true;
        try {
            const method = document.querySelector('input[name="payment"]:checked').value;
            const cash = Number(document.getElementById('checkout-cash').value || 0);
            const payload = pendingPayment?.payload ?? {
                customer_name: document.getElementById('checkout-customer').value.trim(), payment_method: method,
                cash_received: method === 'cash' ? cash : null,
                expected_total: cartTotal().toFixed(2), idempotency_key: paymentKey(),
                items: cart.map(i => ({product_id: i.id, variant: i.variant, quantity: i.qty}))
            };
            if (!payload.customer_name) { window.CiksAlert.notify('Nama pelanggan wajib diisi.', 'warning', 'Data belum lengkap'); return; }
            if (payload.payment_method === 'cash' && units(payload.cash_received) < units(payload.expected_total)) {
                window.CiksAlert.notify('Uang yang diterima kurang dari total pembayaran.', 'warning', 'Nominal belum cukup'); return;
            }
            const approved = await window.CiksAlert.receiptConfirm({
                customer: payload.customer_name, paymentMethod: payload.payment_method === 'cash' ? 'Tunai' : 'QRIS',
                total: 'Rp ' + Number(payload.expected_total).toLocaleString('id-ID'),
            });
            if (!approved) return;
            persistPayment({payload, cart});
            document.querySelectorAll('#checkout-customer, #checkout-cash, input[name="payment"]').forEach(input => input.disabled = true);
            button.textContent = 'Memproses...';
            const response = await fetch(checkoutUrl, {
                method: 'POST',
                headers: {'Content-Type':'application/json', 'Accept':'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content},
                body: JSON.stringify(payload)
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok || !data.success) {
                if (response.status === 422) {
                    persistPayment(null);
                    document.querySelectorAll('#checkout-customer, #checkout-cash, input[name="payment"]').forEach(input => input.disabled = false);
                    if (data.errors?.expected_total) {
                        closeCheckout(true);
                        await refreshCart();
                    }
                }
                const message = data.errors ? Object.values(data.errors)[0]?.[0] : null;
                throw new Error(message || data.message || 'Hasil pembayaran belum dapat diperiksa. Coba kembali untuk memeriksa pembayaran yang sama.');
            }
            persistPayment(null);
            closeCheckout(true);
            cart = []; renderCart();
            window.CiksAlert.notify(data.message || 'Pesanan berhasil diproses.', 'success', 'Pembayaran berhasil');
            showReceipt(data.order);
        } catch (error) {
            const suffix = pendingPayment ? ' Jangan membuat pembayaran baru; tekan kembali Bayar untuk memeriksa pembayaran yang sama.' : '';
            window.CiksAlert.notify((error.message || 'Terjadi kesalahan koneksi.') + suffix, 'error', 'Periksa pembayaran');
        } finally {
            checkoutBusy = false;
            button.disabled = false;
            button.innerHTML = label;
        }
    }

    function showReceipt(order) {
        document.getElementById('rcpt-number').textContent = order.order_number;
        document.getElementById('rcpt-customer').textContent = order.customer_name;
        document.getElementById('rcpt-cashier').textContent = order.cashier;
        document.getElementById('rcpt-time').textContent = order.paid_at;
        document.getElementById('rcpt-payment').textContent = order.payment_method;
        document.getElementById('rcpt-total').textContent = order.formatted_total;
        document.getElementById('rcpt-status').textContent = order.status_label;
        document.getElementById('rcpt-status').className = 'font-bold text-amber-600';
        const rows = order.items.map(item => {
            const row = document.createElement('div'); row.className = 'flex justify-between text-xs';
            const name = document.createElement('span'); name.className = 'text-espresso'; name.textContent = `${item.product_name} x${item.quantity}`;
            const amount = document.createElement('span'); amount.className = 'font-medium text-espresso'; amount.textContent = 'Rp ' + Number(item.subtotal).toLocaleString('id-ID');
            row.append(name, amount); return row;
        });
        document.getElementById('rcpt-items').replaceChildren(...rows);
        if (order.payment_method === 'cash' && order.cash_received) {
            document.getElementById('rcpt-cash-row').classList.remove('hidden');
            document.getElementById('rcpt-change-row').classList.remove('hidden');
            document.getElementById('rcpt-cash').textContent = 'Rp ' + parseFloat(order.cash_received).toLocaleString('id-ID');
            document.getElementById('rcpt-change').textContent = 'Rp ' + parseFloat(order.change_amount).toLocaleString('id-ID');
        } else {
            document.getElementById('rcpt-cash-row').classList.add('hidden');
            document.getElementById('rcpt-change-row').classList.add('hidden');
        }
        document.getElementById('receipt-modal').classList.remove('hidden');
    }
    function closeReceipt() { document.getElementById('receipt-modal').classList.add('hidden'); }
    function printReceipt() {
        const content = document.getElementById('receipt-content').innerHTML;
        const w = window.open('', '_blank', 'width=320,height=600');
        if (!w) return;
        w.document.write(`<html><head><title>Struk</title><style>body{font-family:monospace,sans-serif;font-size:12px;padding:10px;max-width:280px;margin:0 auto}h3{margin:0;font-size:16px;text-align:center}.flex{display:flex;justify-content:space-between}.border-t,.border-b{border-top:1px dashed #ccc;padding-top:8px;margin-top:8px}.mb-4{margin-bottom:12px}.text-center{text-align:center}.font-bold{font-weight:bold}.space-y-1>*+*{margin-top:4px}.space-y-2>*+*{margin-top:6px}span{display:inline-block}@media print{body{padding:0}}</style></head><body>${content}<div class="text-center border-t" style="margin-top:12px;padding-top:8px"><small>*** Terima Kasih ***</small></div></body></html>`);
        w.document.close();
        Promise.all(Array.from(w.document.images, image => image.decode().catch(() => {}))).then(() => {
            w.focus();
            w.print();
            w.close();
        });
    }
    renderCart();
</script>
@endpush
