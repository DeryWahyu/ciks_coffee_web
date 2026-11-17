@extends('layouts.pemilik')
@section('title', 'Ekspor Data')
@section('page-title', 'Ekspor Data')
@section('page-description', 'Unduh data penting Ciks Coffee dalam CSV, Excel, atau PDF')

@section('content')
@php
    $inputClass = 'w-full px-4 py-2.5 bg-cream-light border border-latte rounded-xl text-sm text-espresso focus:outline-none focus:ring-2 focus:ring-caramel/30 focus:border-caramel';
    $labelClass = 'block text-xs font-semibold text-caramel-dark mb-1.5';
    $exportUrls = ['csv' => route('pemilik.exports.csv', [], false), 'excel' => route('pemilik.exports.excel', [], false), 'pdf' => route('pemilik.exports.pdf', [], false)];
    $salesDescriptions = ['csv' => 'Tabel transaksi sesuai filter, dalam satu berkas CSV yang dapat dibuka di aplikasi spreadsheet.', 'excel' => 'Laporan transaksi dalam satu lembar Excel, lengkap dengan jumlah total penjualan.', 'pdf' => 'Laporan penjualan sesuai filter, siap dibaca dan dicetak.'];
@endphp
<div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-5 sm:p-6 mb-6">
    <h2 class="text-sm font-bold text-espresso mb-2">Data yang akan diekspor</h2>
    <p class="text-sm text-caramel-dark mb-4">Simpan data operasional dan laporan usaha dalam satu unduhan.</p>
    <div class="grid sm:grid-cols-3 gap-3 mb-5 text-xs text-caramel-dark">
        <div class="bg-cream-light rounded-xl p-4"><p class="font-semibold text-espresso mb-1">Pesanan dan transaksi</p>Ringkasan omzet, semua status pesanan, rincian item dan pemakaian bahan.</div>
        <div class="bg-cream-light rounded-xl p-4"><p class="font-semibold text-espresso mb-1">Katalog dan persediaan</p>Produk aktif/nonaktif, harga, kategori, resep, bahan baku dan sisa stok.</div>
        <div class="bg-cream-light rounded-xl p-4"><p class="font-semibold text-espresso mb-1">Toko dan pengguna</p>Pelanggan, staf, pengaturan QRIS, denah, meja dan riwayat status meja.</div>
    </div>
    <form id="exportForm" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <div>
            <label for="scope" class="{{ $labelClass }}">Cakupan</label>
            <select name="scope" id="scope" class="{{ $inputClass }}" onchange="updateExportDescription()">
                <option value="all">Seluruh data penting</option><option value="sales">Laporan penjualan saja</option>
            </select>
        </div>
        <div><label for="date_from" class="{{ $labelClass }}">Dari tanggal</label><input type="date" name="date_from" id="date_from" class="{{ $inputClass }}"></div>
        <div><label for="date_to" class="{{ $labelClass }}">Sampai tanggal</label><input type="date" name="date_to" id="date_to" class="{{ $inputClass }}"></div>
        <div>
            <label for="payment_method" class="{{ $labelClass }}">Metode pembayaran</label>
            <select name="payment_method" id="payment_method" class="{{ $inputClass }}"><option value="all">Semua</option><option value="cash">Tunai</option><option value="qris">QRIS</option></select>
        </div>
    </form>
    <div class="flex flex-col sm:flex-row sm:items-start gap-3 justify-between mt-4">
        <p id="scopeNote" class="text-xs text-caramel-dark leading-relaxed max-w-3xl">Tanggal dan metode bayar memfilter pesanan, item dan pemakaian bahannya. Tanggal juga memfilter riwayat meja. Produk, stok, akun dan denah selalu memakai kondisi saat ekspor.</p>
        <button type="button" onclick="resetFilters()" class="text-xs font-semibold text-espresso underline underline-offset-4 shrink-0 self-start">Atur ulang filter</button>
    </div>
    <p class="text-xs text-caramel-dark mt-3">Omzet mengikuti riwayat transaksi. Pesanan mobile yang belum diambil tetap tersimpan dalam data pesanan, tanpa menambah omzet.</p>
</div>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    @foreach([
        'csv' => ['CSV (ZIP)', 'Data dipisah per bagian. Arsip lengkap juga menyertakan gambar produk, bukti bayar, QRIS dan latar denah yang tersedia.'],
        'excel' => ['Excel (XLSX)', 'Satu berkas dengan lembar terpisah untuk setiap bagian. Cocok untuk mengolah dan membandingkan data.'],
        'pdf' => ['PDF', 'Dokumen per bagian yang mudah dibaca dan dicetak. Gunakan CSV (ZIP) jika jumlah data terlalu besar.'],
    ] as $format => [$title, $description])
        <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-5 flex flex-col">
            <h3 id="format-title-{{ $format }}" class="font-bold text-espresso text-sm mb-2">{{ $title }}</h3>
            <p data-export-description data-all-description="{{ $description }}" data-sales-description="{{ $salesDescriptions[$format] }}" class="text-xs text-caramel-dark leading-relaxed mb-5 flex-1">{{ $description }}</p>
            <button type="button" onclick="exportData('{{ $format }}', this)" class="w-full py-2.5 bg-espresso text-cream font-semibold text-sm rounded-xl hover:bg-espresso-light transition-colors disabled:opacity-50">Unduh {{ $format === 'excel' ? 'Excel' : strtoupper($format) }}</button>
        </div>
    @endforeach
</div>

@endsection

@push('scripts')
<script>
function updateExportDescription() {
    const all = document.getElementById('scope').value === 'all';
    document.getElementById('format-title-csv').textContent = all ? 'CSV (ZIP)' : 'CSV';
    document.querySelectorAll('[data-export-description]').forEach(element => {
        element.textContent = all ? element.dataset.allDescription : element.dataset.salesDescription;
    });
    document.getElementById('scopeNote').textContent = all
        ? 'Tanggal dan metode bayar memfilter pesanan, item dan pemakaian bahannya. Tanggal juga memfilter riwayat meja. Produk, stok, akun dan denah selalu memakai kondisi saat ekspor.'
        : 'Hanya transaksi yang masuk riwayat, sesuai rentang tanggal dan metode pembayaran yang dipilih.';
}
async function exportData(format, btn) {
    const form = document.getElementById('exportForm');
    if (!form.reportValidity()) return;
    const params = new URLSearchParams(new FormData(form));
    if (params.get('date_from') && params.get('date_to') && params.get('date_from') > params.get('date_to')) {
        window.CiksAlert.notify('Tanggal akhir harus sama atau setelah tanggal awal.', 'error', 'Periksa filter');
        return;
    }
    const baseUrls = @json($exportUrls);
    const originalText = btn.textContent;
    btn.disabled = true;
    btn.textContent = 'Memproses...';
    try {
        const res = await fetch(baseUrls[format] + '?' + params.toString(), { credentials: 'same-origin', headers: { Accept: 'application/json' } });
        const type = res.headers.get('Content-Type') || '';
        if (!res.ok || type.includes('text/html')) {
            let message = 'Ekspor gagal diproses. Silakan coba lagi.';
            if (res.status === 401 || type.includes('text/html')) message = 'Sesi telah berakhir. Silakan masuk kembali.';
            if (type.includes('application/json')) {
                const data = await res.json();
                message = Object.values(data.errors || {}).flat()[0] || data.message || message;
            }
            window.CiksAlert.notify(message, 'error', 'Ekspor tidak dapat diproses');
            return;
        }
        const blob = await res.blob();
        const disposition = res.headers.get('Content-Disposition') || '';
        const match = disposition.match(/filename\*?=(?:UTF-8'')?"?([^";]+)"?/i);
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = match ? decodeURIComponent(match[1]) : 'cikscoffee.' + ({csv: params.get('scope') === 'all' ? 'zip' : 'csv', excel: 'xlsx', pdf: 'pdf'}[format]);
        document.body.appendChild(link);
        link.click();
        link.remove();
        setTimeout(() => URL.revokeObjectURL(url), 2000);
    } catch (error) {
        window.CiksAlert.notify('Koneksi terputus saat mengekspor. Silakan coba lagi.', 'error', 'Ekspor gagal');
    } finally {
        btn.disabled = false;
        btn.textContent = originalText;
    }
}
function resetFilters() {
    document.getElementById('exportForm').reset();
    updateExportDescription();
}
</script>
@endpush
