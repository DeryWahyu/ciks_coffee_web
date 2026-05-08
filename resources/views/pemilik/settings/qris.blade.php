@extends('layouts.pemilik')
@section('title', 'QRIS Toko')
@section('page-title', 'QRIS Toko')
@section('page-description', 'Kelola gambar QRIS untuk pembayaran digital pelanggan')

@section('content')
<div class="max-w-2xl">
    {{-- Current QRIS Preview --}}
    <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-6 mb-6">
        <h4 class="text-sm font-bold text-espresso mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-espresso" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 6.75h.75v.75h-.75v-.75zM6.75 16.5h.75v.75h-.75v-.75zM16.5 6.75h.75v.75h-.75v-.75zM13.5 13.5h.75v.75h-.75v-.75zM13.5 19.5h.75v.75h-.75v-.75zM19.5 13.5h.75v.75h-.75v-.75zM19.5 19.5h.75v.75h-.75v-.75zM16.5 16.5h.75v.75h-.75v-.75z"/>
            </svg>
            Gambar QRIS Saat Ini
        </h4>

        @if($qrisImage)
            <div class="flex flex-col items-center">
                <div class="relative group">
                    <img src="{{ asset('storage/' . $qrisImage) }}"
                         alt="QRIS Ciks Coffee"
                         class="w-64 h-64 object-contain rounded-xl border-2 border-latte/40 bg-cream-light p-2">
                    <div class="absolute inset-0 bg-black/40 rounded-xl opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                        <span class="text-white text-sm font-medium">QRIS Aktif</span>
                    </div>
                </div>
                <p class="text-xs text-caramel-dark mt-3">Gambar ini akan ditampilkan pada aplikasi mobile saat pelanggan memilih pembayaran QRIS.</p>

                {{-- Delete button --}}
                <form method="POST" action="{{ route('pemilik.settings.qris.delete') }}" class="mt-4"
                      onsubmit="return confirm('Yakin ingin menghapus gambar QRIS?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-red-50 text-red-600 text-sm font-medium rounded-xl border border-red-200 hover:bg-red-100 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                        Hapus QRIS
                    </button>
                </form>
            </div>
        @else
            <div class="flex flex-col items-center py-8">
                <div class="w-24 h-24 bg-latte/20 rounded-2xl flex items-center justify-center mb-4">
                    <svg class="w-10 h-10 text-caramel" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z"/>
                    </svg>
                </div>
                <p class="text-sm text-caramel-dark font-medium">Belum ada gambar QRIS</p>
                <p class="text-xs text-caramel mt-1">Upload gambar QRIS agar pelanggan dapat melakukan pembayaran digital.</p>
            </div>
        @endif
    </div>

    {{-- Upload Form --}}
    <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-6">
        <h4 class="text-sm font-bold text-espresso mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-espresso" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
            </svg>
            {{ $qrisImage ? 'Ganti Gambar QRIS' : 'Upload Gambar QRIS' }}
        </h4>

        <form method="POST" action="{{ route('pemilik.settings.qris.update') }}" enctype="multipart/form-data">
            @csrf

            <div class="mb-4">
                <label class="block text-sm font-medium text-espresso mb-2">Pilih Gambar</label>
                <div class="relative">
                    <input type="file" name="qris_image" id="qris_image" accept="image/jpeg,image/png,image/webp"
                           class="block w-full text-sm text-espresso/70 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-espresso/10 file:text-espresso hover:file:bg-espresso/20 file:cursor-pointer cursor-pointer border border-latte/50 rounded-xl"
                           required>
                </div>
                <p class="mt-2 text-xs text-caramel">Format: JPG, PNG, WebP. Maksimal 2MB. Disarankan rasio 1:1.</p>
                @error('qris_image')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Image preview --}}
            <div id="preview-container" class="mb-4 hidden">
                <p class="text-xs font-medium text-caramel-dark mb-2">Preview:</p>
                <img id="preview-image" src="" alt="Preview" class="w-48 h-48 object-contain rounded-xl border border-latte/40 bg-cream-light p-1">
            </div>

            <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-espresso text-cream text-sm font-semibold rounded-xl hover:bg-espresso-light transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                {{ $qrisImage ? 'Perbarui QRIS' : 'Upload QRIS' }}
            </button>
        </form>
    </div>
</div>

@push('scripts')
<script>
    document.getElementById('qris_image').addEventListener('change', function(e) {
        const file = e.target.files[0];
        const preview = document.getElementById('preview-container');
        const img = document.getElementById('preview-image');
        if (file) {
            const reader = new FileReader();
            reader.onload = (ev) => { img.src = ev.target.result; preview.classList.remove('hidden'); };
            reader.readAsDataURL(file);
        } else {
            preview.classList.add('hidden');
        }
    });
</script>
@endpush
@endsection
