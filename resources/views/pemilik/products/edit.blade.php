@extends('layouts.pemilik')

@section('title', 'Edit Produk')
@section('page-title', 'Edit Produk')
@section('page-description', 'Perbarui detail produk "{{ $product->name }}"')

@section('content')
    <div class="max-w-2xl">
        <form method="POST" action="{{ route('pemilik.products.update', $product) }}" enctype="multipart/form-data" class="space-y-6">
            @csrf @method('PUT')

            <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-6 space-y-5">
                <h3 class="text-sm font-bold text-espresso uppercase tracking-wider">Informasi Produk</h3>

                {{-- Name --}}
                <div>
                    <label for="name" class="block text-sm font-semibold text-espresso mb-1.5">Nama Produk <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" value="{{ old('name', $product->name) }}" required class="w-full px-4 py-2.5 text-sm bg-white border border-latte/60 rounded-xl focus:ring-2 focus:ring-caramel/30 focus:border-caramel outline-none transition">
                    @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Category --}}
                <div>
                    <label for="category_id" class="block text-sm font-semibold text-espresso mb-1.5">Kategori <span class="text-red-500">*</span></label>
                    <select name="category_id" id="category_id" required class="w-full px-4 py-2.5 text-sm bg-white border border-latte/60 rounded-xl focus:ring-2 focus:ring-caramel/30 focus:border-caramel outline-none transition" onchange="toggleLitePrice(this)">
                        <option value="">Pilih Kategori</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" data-slug="{{ $cat->slug }}" {{ old('category_id', $product->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Description --}}
                <div>
                    <label for="description" class="block text-sm font-semibold text-espresso mb-1.5">Deskripsi</label>
                    <textarea name="description" id="description" rows="3" class="w-full px-4 py-2.5 text-sm bg-white border border-latte/60 rounded-xl focus:ring-2 focus:ring-caramel/30 focus:border-caramel outline-none transition resize-none">{{ old('description', $product->description) }}</textarea>
                    @error('description') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Image Upload --}}
                <div>
                    <label class="block text-sm font-semibold text-espresso mb-1.5">Foto Produk</label>
                    <div class="relative">
                        <input type="file" name="image" id="image" accept="image/jpg,image/jpeg,image/png,image/webp" class="hidden" onchange="previewImage(this)">
                        <div id="image-preview-area" class="w-full h-40 border-2 border-dashed border-latte/60 rounded-xl flex flex-col items-center justify-center cursor-pointer hover:border-caramel/60 transition overflow-hidden" onclick="document.getElementById('image').click()">
                            <div id="image-placeholder" class="{{ $product->image ? 'hidden' : '' }} flex flex-col items-center">
                                <svg class="w-10 h-10 text-caramel/40 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                                <p class="text-xs text-caramel">Klik untuk ganti gambar</p>
                                <p class="text-[0.65rem] text-caramel/60 mt-1">JPG, PNG, WebP — Maks 2MB</p>
                            </div>
                            <img id="image-preview" class="{{ $product->image ? '' : 'hidden' }} w-full h-full object-cover rounded-xl" src="{{ $product->image ? asset('storage/' . $product->image) : '' }}" alt="Preview">
                        </div>
                    </div>
                    @error('image') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Pricing --}}
            <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-6 space-y-5">
                <h3 class="text-sm font-bold text-espresso uppercase tracking-wider">Pengaturan Harga</h3>

                <div>
                    <label for="price" class="block text-sm font-semibold text-espresso mb-1.5">Harga <span id="price-label-type"></span> <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm text-caramel font-medium">Rp</span>
                        <input type="number" name="price" id="price" value="{{ old('price', $product->price) }}" required min="0" step="500" class="w-full pl-12 pr-4 py-2.5 text-sm bg-white border border-latte/60 rounded-xl focus:ring-2 focus:ring-caramel/30 focus:border-caramel outline-none transition">
                    </div>
                    @error('price') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div id="lite-price-group" class="{{ $product->category->isCoffee() ? '' : 'hidden' }}">
                    <label for="price_lite" class="block text-sm font-semibold text-espresso mb-1.5">Harga Lite</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm text-caramel font-medium">Rp</span>
                        <input type="number" name="price_lite" id="price_lite" value="{{ old('price_lite', $product->price_lite) }}" min="0" step="500" class="w-full pl-12 pr-4 py-2.5 text-sm bg-white border border-latte/60 rounded-xl focus:ring-2 focus:ring-caramel/30 focus:border-caramel outline-none transition">
                    </div>
                    <p class="text-[0.65rem] text-caramel mt-1">Harga untuk varian lite / ukuran kecil (khusus Coffee)</p>
                    @error('price_lite') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Komposisi Bahan Baku (Non-Coffee) --}}
            <div id="ingredients-section-default" class="bg-white rounded-2xl shadow-sm border border-latte/50 p-6 space-y-5">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold text-espresso uppercase tracking-wider">Komposisi Bahan Baku</h3>
                    <button type="button" onclick="addIngredientRow('default')" class="inline-flex items-center gap-1.5 text-xs font-semibold text-espresso bg-espresso/5 hover:bg-espresso/15 px-3 py-1.5 rounded-lg transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        Tambah Bahan
                    </button>
                </div>
                <div id="ingredients-container-default" class="space-y-3"></div>
                <p class="text-[0.65rem] text-caramel">Tentukan bahan baku dan takaran yang dibutuhkan untuk membuat produk ini.</p>
            </div>

            {{-- Komposisi Bahan Baku — Coffee Base --}}
            <div id="ingredients-section-base" class="bg-white rounded-2xl shadow-sm border border-latte/50 p-6 space-y-5 hidden">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-espresso uppercase tracking-wider">Komposisi — Base</h3>
                        <p class="text-[0.65rem] text-caramel mt-0.5">Bahan baku untuk varian Base (reguler)</p>
                    </div>
                    <button type="button" onclick="addIngredientRow('base')" class="inline-flex items-center gap-1.5 text-xs font-semibold text-espresso bg-espresso/5 hover:bg-espresso/15 px-3 py-1.5 rounded-lg transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        Tambah Bahan
                    </button>
                </div>
                <div id="ingredients-container-base" class="space-y-3"></div>
            </div>

            {{-- Komposisi Bahan Baku — Coffee Lite --}}
            <div id="ingredients-section-lite" class="bg-white rounded-2xl shadow-sm border border-latte/50 p-6 space-y-5 hidden">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-espresso uppercase tracking-wider">Komposisi — Lite</h3>
                        <p class="text-[0.65rem] text-caramel mt-0.5">Bahan baku untuk varian Lite (ukuran kecil)</p>
                    </div>
                    <button type="button" onclick="addIngredientRow('lite')" class="inline-flex items-center gap-1.5 text-xs font-semibold text-espresso bg-espresso/5 hover:bg-espresso/15 px-3 py-1.5 rounded-lg transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        Tambah Bahan
                    </button>
                </div>
                <div id="ingredients-container-lite" class="space-y-3"></div>
            </div>

            {{-- Status --}}
            <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-6">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }} class="w-5 h-5 rounded-lg border-latte/60 text-espresso focus:ring-caramel/30">
                    <div>
                        <p class="text-sm font-semibold text-espresso">Aktifkan Produk</p>
                        <p class="text-xs text-caramel">Produk aktif akan tampil di daftar menu</p>
                    </div>
                </label>
            </div>

            {{-- Submit --}}
            <div class="flex items-center gap-3">
                <button type="submit" class="inline-flex items-center gap-2 bg-espresso hover:bg-espresso-light text-cream text-sm font-semibold px-6 py-3 rounded-xl transition-all duration-200 hover:shadow-md">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                    Simpan Perubahan
                </button>
                <a href="{{ route('pemilik.products.index') }}" class="text-sm text-caramel hover:text-espresso transition font-medium px-4 py-3">Batal</a>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
    function toggleLitePrice(select) {
        const option = select.options[select.selectedIndex];
        const slug = option?.dataset?.slug || '';
        const liteGroup = document.getElementById('lite-price-group');
        const priceLabel = document.getElementById('price-label-type');
        const sectionDefault = document.getElementById('ingredients-section-default');
        const sectionBase = document.getElementById('ingredients-section-base');
        const sectionLite = document.getElementById('ingredients-section-lite');

        if (slug === 'coffee') {
            liteGroup.classList.remove('hidden');
            priceLabel.textContent = '(Base)';
            sectionDefault.classList.add('hidden');
            sectionBase.classList.remove('hidden');
            sectionLite.classList.remove('hidden');
        } else {
            liteGroup.classList.add('hidden');
            priceLabel.textContent = '';
            document.getElementById('price_lite').value = '';
            sectionDefault.classList.remove('hidden');
            sectionBase.classList.add('hidden');
            sectionLite.classList.add('hidden');
        }
    }

    function previewImage(input) {
        const preview = document.getElementById('image-preview');
        const placeholder = document.getElementById('image-placeholder');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = (e) => {
                preview.src = e.target.result;
                preview.classList.remove('hidden');
                placeholder.classList.add('hidden');
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    // === Ingredient Rows (variant-aware) ===
    const ingredientsData = @json($ingredients);
    let ingredientIndex = 0;

    function getFieldName(variant) {
        if (variant === 'base') return 'ingredients_base';
        if (variant === 'lite') return 'ingredients_lite';
        return 'ingredients';
    }

    function addIngredientRow(variant, selectedId = '', selectedQty = '') {
        const container = document.getElementById('ingredients-container-' + variant);
        const idx = ingredientIndex++;
        const fieldName = getFieldName(variant);
        const row = document.createElement('div');
        row.className = 'flex items-center gap-3';
        row.id = 'ingredient-row-' + idx;

        let options = '<option value="">Pilih Bahan Baku</option>';
        ingredientsData.forEach(ing => {
            const sel = ing.id == selectedId ? 'selected' : '';
            options += `<option value="${ing.id}" ${sel}>${ing.nama_bahan} (${ing.satuan})</option>`;
        });

        row.innerHTML = `
            <div class="flex-1">
                <select name="${fieldName}[${idx}][id]" required class="w-full px-3 py-2 text-sm bg-white border border-latte/60 rounded-xl focus:ring-2 focus:ring-caramel/30 focus:border-caramel outline-none transition">${options}</select>
            </div>
            <div class="w-32">
                <input type="number" name="${fieldName}[${idx}][quantity]" value="${selectedQty}" required min="0.01" step="0.01" placeholder="Takaran" class="w-full px-3 py-2 text-sm bg-white border border-latte/60 rounded-xl focus:ring-2 focus:ring-caramel/30 focus:border-caramel outline-none transition">
            </div>
            <button type="button" onclick="removeIngredientRow(${idx})" class="p-2 rounded-lg text-red-400 hover:bg-red-50 hover:text-red-600 transition shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        `;
        container.appendChild(row);
    }

    function removeIngredientRow(idx) {
        const row = document.getElementById('ingredient-row-' + idx);
        if (row) row.remove();
    }

    document.addEventListener('DOMContentLoaded', () => {
        const select = document.getElementById('category_id');
        if (select.value) toggleLitePrice(select);

        // Pre-populate existing ingredients grouped by variant
        const existing = @json($product->ingredients->map(fn($i) => ['id' => $i->id, 'quantity' => $i->pivot->quantity, 'variant' => $i->pivot->variant]));
        existing.forEach(item => {
            const variant = item.variant || 'default';
            addIngredientRow(variant, item.id, item.quantity);
        });
    });
</script>
@endpush
