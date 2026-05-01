@extends('layouts.pemilik')

@section('title', 'Kategori Produk')
@section('page-title', 'Kelola Kategori Produk')
@section('page-description', 'Tambah dan atur kategori menu')

@section('page-actions')
    <a href="{{ route('pemilik.products.index') }}" class="inline-flex items-center gap-2 bg-espresso/10 hover:bg-espresso/20 text-espresso text-sm font-medium px-4 py-2.5 rounded-xl transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"/></svg>
        Kembali ke Produk
    </a>
@endsection

@section('content')
    <div class="w-full space-y-6">
        {{-- Add Category Form --}}
        <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-6">
            <h3 class="text-sm font-bold text-espresso uppercase tracking-wider mb-4">Tambah Kategori Baru</h3>
            <form method="POST" action="{{ route('pemilik.categories.store') }}" class="flex items-end gap-3">
                @csrf
                <div class="flex-1">
                    <label for="name" class="block text-sm font-semibold text-espresso mb-1.5">Nama Kategori</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required class="w-full px-4 py-2.5 text-sm bg-white border border-latte/60 rounded-xl focus:ring-2 focus:ring-caramel/30 focus:border-caramel outline-none transition" placeholder="Contoh: Juice">
                    @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <button type="submit" class="inline-flex items-center gap-2 bg-espresso hover:bg-espresso-light text-cream text-sm font-semibold px-5 py-2.5 rounded-xl transition-all hover:shadow-md shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Tambah
                </button>
            </form>
        </div>

        {{-- Categories List --}}
        <div class="bg-white rounded-2xl shadow-sm border border-latte/50 overflow-hidden">
            <div class="px-6 py-4 border-b border-latte/30">
                <h3 class="text-sm font-bold text-espresso uppercase tracking-wider">Daftar Kategori</h3>
            </div>
            <div class="divide-y divide-latte/20">
                @forelse ($categories as $category)
                    <div class="px-6 py-4 flex items-center justify-between gap-4 hover:bg-cream/30 transition" id="category-{{ $category->id }}">
                        <div class="flex items-center gap-3 flex-1 min-w-0">
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center {{ $category->is_active ? 'bg-espresso/10' : 'bg-red-50' }}">
                                <svg class="w-4 h-4 {{ $category->is_active ? 'text-espresso' : 'text-red-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z"/></svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-espresso truncate {{ !$category->is_active ? 'line-through opacity-50' : '' }}">{{ $category->name }}</p>
                                <p class="text-xs text-caramel">{{ $category->products_count }} produk</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            {{-- Edit inline --}}
                            <button onclick="editCategory({{ $category->id }}, '{{ $category->name }}')" class="p-2 rounded-lg text-caramel hover:bg-espresso/5 hover:text-espresso transition" title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                            </button>
                            {{-- Toggle Status --}}
                            <form method="POST" action="{{ route('pemilik.categories.toggle-status', $category) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="p-2 rounded-lg transition {{ $category->is_active ? 'text-red-400 hover:bg-red-50 hover:text-red-600' : 'text-green-500 hover:bg-green-50 hover:text-green-600' }}" title="{{ $category->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                    @if ($category->is_active)
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                                    @else
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    @endif
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="px-6 py-12 text-center">
                        <p class="text-sm text-caramel">Belum ada kategori. Tambahkan kategori pertama di atas.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Edit Category Modal --}}
    <div id="edit-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closeEditModal()"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-white rounded-2xl shadow-xl border border-latte/50 p-6 w-full max-w-md">
            <h3 class="text-sm font-bold text-espresso uppercase tracking-wider mb-4">Edit Kategori</h3>
            <form id="edit-form" method="POST">
                @csrf @method('PUT')
                <div class="mb-4">
                    <label for="edit-name" class="block text-sm font-semibold text-espresso mb-1.5">Nama Kategori</label>
                    <input type="text" name="name" id="edit-name" required class="w-full px-4 py-2.5 text-sm bg-white border border-latte/60 rounded-xl focus:ring-2 focus:ring-caramel/30 focus:border-caramel outline-none transition">
                </div>
                <div class="flex items-center gap-3">
                    <button type="submit" class="bg-espresso hover:bg-espresso-light text-cream text-sm font-semibold px-5 py-2.5 rounded-xl transition-all">Simpan</button>
                    <button type="button" onclick="closeEditModal()" class="text-sm text-caramel hover:text-espresso transition font-medium px-4 py-2.5">Batal</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function editCategory(id, name) {
        document.getElementById('edit-name').value = name;
        document.getElementById('edit-form').action = `/pemilik/categories/${id}`;
        document.getElementById('edit-modal').classList.remove('hidden');
    }

    function closeEditModal() {
        document.getElementById('edit-modal').classList.add('hidden');
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeEditModal();
    });
</script>
@endpush
