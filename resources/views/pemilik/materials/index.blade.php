@extends('layouts.pemilik')

@section('title', 'Bahan Baku')
@section('page-title', 'Kelola Bahan Baku')
@section('page-description', 'Atur stok dan data bahan baku')

@section('content')
    <div class="w-full space-y-6">
        {{-- Add Ingredient Form --}}
        <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-6">
            <h3 class="text-sm font-bold text-espresso uppercase tracking-wider mb-4">Tambah Bahan Baku Baru</h3>
            <form method="POST" action="{{ route('pemilik.materials.store') }}" class="flex flex-wrap items-end gap-3">
                @csrf
                <div class="flex-1 min-w-[180px]">
                    <label for="nama_bahan" class="block text-sm font-semibold text-espresso mb-1.5">Nama Bahan <span class="text-red-500">*</span></label>
                    <input type="text" name="nama_bahan" id="nama_bahan" value="{{ old('nama_bahan') }}" required class="w-full px-4 py-2.5 text-sm bg-white border border-latte/60 rounded-xl focus:ring-2 focus:ring-caramel/30 focus:border-caramel outline-none transition" placeholder="Contoh: Kopi Arabica">
                    @error('nama_bahan') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="w-32">
                    <label for="satuan" class="block text-sm font-semibold text-espresso mb-1.5">Satuan <span class="text-red-500">*</span></label>
                    <input type="text" name="satuan" id="satuan" value="{{ old('satuan') }}" required class="w-full px-4 py-2.5 text-sm bg-white border border-latte/60 rounded-xl focus:ring-2 focus:ring-caramel/30 focus:border-caramel outline-none transition" placeholder="gram">
                    @error('satuan') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="w-32">
                    <label for="stok" class="block text-sm font-semibold text-espresso mb-1.5">Stok Awal <span class="text-red-500">*</span></label>
                    <input type="number" name="stok" id="stok" value="{{ old('stok', 0) }}" required min="0" step="0.01" class="w-full px-4 py-2.5 text-sm bg-white border border-latte/60 rounded-xl focus:ring-2 focus:ring-caramel/30 focus:border-caramel outline-none transition" placeholder="0">
                    @error('stok') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <button type="submit" class="inline-flex items-center gap-2 bg-espresso hover:bg-espresso-light text-cream text-sm font-semibold px-5 py-2.5 rounded-xl transition-all hover:shadow-md shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Tambah
                </button>
            </form>
        </div>

        {{-- Search --}}
        <form method="GET" action="{{ route('pemilik.materials.index') }}" class="flex items-center gap-3">
            <div class="relative flex-1">
                <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-caramel" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari bahan baku..." class="w-full pl-10 pr-4 py-2.5 text-sm bg-white border border-latte/60 rounded-xl focus:ring-2 focus:ring-caramel/30 focus:border-caramel outline-none transition">
            </div>
            <button type="submit" class="bg-espresso/10 hover:bg-espresso/20 text-espresso text-sm font-medium px-4 py-2.5 rounded-xl transition">Cari</button>
            @if(request('search'))
                <a href="{{ route('pemilik.materials.index') }}" class="text-sm text-caramel hover:text-espresso transition">Reset</a>
            @endif
        </form>

        {{-- Ingredients Table --}}
        <div class="bg-white rounded-2xl shadow-sm border border-latte/50 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full" id="materials-table">
                    <thead>
                        <tr class="border-b border-latte/40">
                            <th class="text-left px-6 py-4 text-xs font-bold text-espresso uppercase tracking-wider">Nama Bahan</th>
                            <th class="text-left px-6 py-4 text-xs font-bold text-espresso uppercase tracking-wider">Satuan</th>
                            <th class="text-right px-6 py-4 text-xs font-bold text-espresso uppercase tracking-wider">Stok</th>
                            <th class="text-center px-6 py-4 text-xs font-bold text-espresso uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-latte/20">
                        @forelse ($ingredients as $item)
                            <tr class="hover:bg-cream/30 transition-colors" id="ingredient-{{ $item->id }}">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 bg-espresso/10 rounded-lg flex items-center justify-center">
                                            <svg class="w-4 h-4 text-espresso" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375"/></svg>
                                        </div>
                                        <span class="text-sm font-medium text-espresso">{{ $item->nama_bahan }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-caramel-dark">{{ $item->satuan }}</td>
                                <td class="px-6 py-4 text-right">
                                    <span class="text-sm font-semibold {{ $item->stok <= 10 ? 'text-red-600' : 'text-espresso' }}">
                                        {{ number_format($item->stok, $item->stok == intval($item->stok) ? 0 : 2, ',', '.') }}
                                    </span>
                                    @if ($item->stok <= 10)
                                        <span class="ml-1 text-[0.6rem] font-bold text-red-500 uppercase">Low</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <button onclick="editIngredient({{ $item->id }}, '{{ addslashes($item->nama_bahan) }}', '{{ $item->satuan }}', {{ $item->stok }})" class="p-2 rounded-lg text-caramel hover:bg-espresso/5 hover:text-espresso transition" title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                                        </button>
                                        <form method="POST" action="{{ route('pemilik.materials.destroy', $item) }}" onsubmit="return confirm('Yakin hapus bahan baku ini?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="p-2 rounded-lg text-red-400 hover:bg-red-50 hover:text-red-600 transition" title="Hapus">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="w-14 h-14 bg-latte/30 rounded-2xl flex items-center justify-center mb-3">
                                            <svg class="w-7 h-7 text-caramel" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375"/></svg>
                                        </div>
                                        <p class="text-sm text-caramel-dark font-medium">Belum ada bahan baku</p>
                                        <p class="text-xs text-caramel mt-1">Tambahkan bahan baku pertama menggunakan form di atas.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($ingredients->hasPages())
                <div class="px-6 py-4 border-t border-latte/30">
                    {{ $ingredients->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- Edit Modal --}}
    <div id="edit-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closeEditModal()"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-white rounded-2xl shadow-xl border border-latte/50 p-6 w-full max-w-md">
            <h3 class="text-sm font-bold text-espresso uppercase tracking-wider mb-4">Edit Bahan Baku</h3>
            <form id="edit-form" method="POST" class="space-y-4">
                @csrf @method('PUT')
                <div>
                    <label for="edit-nama" class="block text-sm font-semibold text-espresso mb-1.5">Nama Bahan</label>
                    <input type="text" name="nama_bahan" id="edit-nama" required class="w-full px-4 py-2.5 text-sm bg-white border border-latte/60 rounded-xl focus:ring-2 focus:ring-caramel/30 focus:border-caramel outline-none transition">
                </div>
                <div class="flex gap-3">
                    <div class="flex-1">
                        <label for="edit-satuan" class="block text-sm font-semibold text-espresso mb-1.5">Satuan</label>
                        <input type="text" name="satuan" id="edit-satuan" required class="w-full px-4 py-2.5 text-sm bg-white border border-latte/60 rounded-xl focus:ring-2 focus:ring-caramel/30 focus:border-caramel outline-none transition">
                    </div>
                    <div class="flex-1">
                        <label for="edit-stok" class="block text-sm font-semibold text-espresso mb-1.5">Stok</label>
                        <input type="number" name="stok" id="edit-stok" required min="0" step="0.01" class="w-full px-4 py-2.5 text-sm bg-white border border-latte/60 rounded-xl focus:ring-2 focus:ring-caramel/30 focus:border-caramel outline-none transition">
                    </div>
                </div>
                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="bg-espresso hover:bg-espresso-light text-cream text-sm font-semibold px-5 py-2.5 rounded-xl transition-all">Simpan</button>
                    <button type="button" onclick="closeEditModal()" class="text-sm text-caramel hover:text-espresso transition font-medium px-4 py-2.5">Batal</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function editIngredient(id, nama, satuan, stok) {
        document.getElementById('edit-nama').value = nama;
        document.getElementById('edit-satuan').value = satuan;
        document.getElementById('edit-stok').value = stok;
        document.getElementById('edit-form').action = `/pemilik/materials/${id}`;
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
