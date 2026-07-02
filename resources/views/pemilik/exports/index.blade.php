@extends('layouts.pemilik')
@section('title', 'Ekspor Data')
@section('page-title', 'Ekspor Data')
@section('page-description', 'Ekspor laporan penjualan ke CSV, Excel, atau PDF')

@section('content')
{{-- Filter --}}
<div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-5 sm:p-6 mb-6">
    <h4 class="text-sm font-bold text-espresso mb-4 flex items-center gap-2">
        <svg class="w-4.5 h-4.5 text-caramel" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z"/></svg>
        Filter Data Sebelum Ekspor
    </h4>
    <form id="exportForm" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div>
            <label class="block text-xs font-semibold text-caramel uppercase tracking-wider mb-1.5">Dari Tanggal</label>
            <input type="date" name="date_from" id="date_from" class="w-full px-4 py-2.5 bg-cream-light border border-latte rounded-xl text-sm text-espresso focus:outline-none focus:ring-2 focus:ring-caramel/30 focus:border-caramel transition-all">
        </div>
        <div>
            <label class="block text-xs font-semibold text-caramel uppercase tracking-wider mb-1.5">Sampai Tanggal</label>
            <input type="date" name="date_to" id="date_to" class="w-full px-4 py-2.5 bg-cream-light border border-latte rounded-xl text-sm text-espresso focus:outline-none focus:ring-2 focus:ring-caramel/30 focus:border-caramel transition-all">
        </div>
        <div>
            <label class="block text-xs font-semibold text-caramel uppercase tracking-wider mb-1.5">Metode Bayar</label>
            <select name="payment_method" id="payment_method" class="w-full px-4 py-2.5 bg-cream-light border border-latte rounded-xl text-sm text-espresso focus:outline-none focus:ring-2 focus:ring-caramel/30 focus:border-caramel transition-all">
                <option value="all">Semua</option>
                <option value="cash">Cash</option>
                <option value="qris">QRIS</option>
            </select>
        </div>
        <div class="flex items-end">
            <button type="button" onclick="resetFilters()" class="w-full px-4 py-2.5 text-sm font-semibold text-caramel border border-latte rounded-xl hover:bg-cream/50 transition-all">Atur Ulang Filter</button>
        </div>
    </form>
</div>

{{-- Export Buttons --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-6">
    {{-- CSV --}}
    <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-6 hover:shadow-md transition-all group">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-12 h-12 bg-green-50 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 01-1.125-1.125M3.375 19.5h7.5c.621 0 1.125-.504 1.125-1.125m-9.75 0V5.625m0 12.75v-1.5c0-.621.504-1.125 1.125-1.125m18.375 2.625V5.625m0 12.75c0 .621-.504 1.125-1.125 1.125m1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125m0 3.75h-7.5A1.125 1.125 0 0112 18.375m9.75-12.75c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125m19.5 0v1.5c0 .621-.504 1.125-1.125 1.125M2.25 5.625v1.5c0 .621.504 1.125 1.125 1.125m0 0h17.25m-17.25 0h7.5c.621 0 1.125.504 1.125 1.125M3.375 8.25c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125m17.25-3.75h-7.5c-.621 0-1.125.504-1.125 1.125m8.625-1.125c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m-17.25 0h7.5m-7.5 0c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125M12 10.875v-1.5m0 1.5c0 .621-.504 1.125-1.125 1.125M12 10.875c0 .621.504 1.125 1.125 1.125m-2.25 0c.621 0 1.125.504 1.125 1.125M13.125 12h7.5m-7.5 0c-.621 0-1.125.504-1.125 1.125M20.625 12c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m-17.25 0h7.5M12 14.625v-1.5m0 1.5c0 .621-.504 1.125-1.125 1.125M12 14.625c0 .621.504 1.125 1.125 1.125m-2.25 0c.621 0 1.125.504 1.125 1.125m0 0v.375"/></svg>
            </div>
            <div>
                <h5 class="text-sm font-bold text-espresso">CSV</h5>
                <p class="text-xs text-caramel-dark">Comma-Separated Values</p>
            </div>
        </div>
        <p class="text-xs text-caramel-dark mb-4">Format ringan yang dapat dibuka di Excel, Google Sheets, dan aplikasi spreadsheet lainnya.</p>
        <button onclick="exportData('csv', this)" class="w-full py-2.5 bg-green-600 text-white font-semibold text-sm rounded-xl hover:bg-green-700 transition-all flex items-center justify-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
            Unduh CSV
        </button>
    </div>

    {{-- Excel --}}
    <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-6 hover:shadow-md transition-all group">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-12 h-12 bg-emerald-50 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
            </div>
            <div>
                <h5 class="text-sm font-bold text-espresso">Excel (XLSX)</h5>
                <p class="text-xs text-caramel-dark">Microsoft Excel Format</p>
            </div>
        </div>
        <p class="text-xs text-caramel-dark mb-4">Format Excel lengkap dengan header berwarna, auto-width kolom, dan format angka otomatis.</p>
        <button onclick="exportData('excel', this)" class="w-full py-2.5 bg-emerald-600 text-white font-semibold text-sm rounded-xl hover:bg-emerald-700 transition-all flex items-center justify-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
            Unduh Excel
        </button>
    </div>

    {{-- PDF --}}
    <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-6 hover:shadow-md transition-all group">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-12 h-12 bg-red-50 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
            </div>
            <div>
                <h5 class="text-sm font-bold text-espresso">PDF</h5>
                <p class="text-xs text-caramel-dark">Portable Document Format</p>
            </div>
        </div>
        <p class="text-xs text-caramel-dark mb-4">Laporan PDF landscape dengan branding Ciks Coffee, siap untuk dicetak atau di-share.</p>
        <button onclick="exportData('pdf', this)" class="w-full py-2.5 bg-red-600 text-white font-semibold text-sm rounded-xl hover:bg-red-700 transition-all flex items-center justify-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
            Unduh PDF
        </button>
    </div>
</div>

{{-- Jadwal Ekspor Otomatis --}}
<div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-5 sm:p-6">
    <h4 class="text-sm font-bold text-espresso mb-2 flex items-center gap-2">
        <svg class="w-4.5 h-4.5 text-caramel" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        Jadwal Ekspor Otomatis
    </h4>
    <p class="text-xs text-caramel-dark mb-5">Atur jadwal ekspor otomatis agar laporan dikirim berkala ke email Anda.</p>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-5">
        <div>
            <label class="block text-xs font-semibold text-caramel uppercase tracking-wider mb-1.5">Frekuensi</label>
            <select id="schedule_freq" class="w-full px-4 py-2.5 bg-cream-light border border-latte rounded-xl text-sm text-espresso focus:outline-none focus:ring-2 focus:ring-caramel/30 focus:border-caramel transition-all">
                <option value="daily">Harian</option>
                <option value="weekly" selected>Mingguan</option>
                <option value="monthly">Bulanan</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-caramel uppercase tracking-wider mb-1.5">Format</label>
            <select id="schedule_format" class="w-full px-4 py-2.5 bg-cream-light border border-latte rounded-xl text-sm text-espresso focus:outline-none focus:ring-2 focus:ring-caramel/30 focus:border-caramel transition-all">
                <option value="csv">CSV</option>
                <option value="excel" selected>Excel (XLSX)</option>
                <option value="pdf">PDF</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-caramel uppercase tracking-wider mb-1.5">Email Tujuan</label>
            <input type="email" id="schedule_email" value="{{ Auth::user()->email }}" class="w-full px-4 py-2.5 bg-cream-light border border-latte rounded-xl text-sm text-espresso focus:outline-none focus:ring-2 focus:ring-caramel/30 focus:border-caramel transition-all">
        </div>
    </div>

    <div class="flex items-center justify-between p-4 bg-amber-50 rounded-xl border border-amber-200">
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
            <p class="text-xs text-amber-800"><span class="font-semibold">Segera Hadir:</span> Fitur jadwal ekspor otomatis sedang dalam pengembangan. Saat ini Anda dapat melakukan ekspor manual menggunakan tombol di atas.</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
async function exportData(format, btn) {
    const params = new URLSearchParams();
    const dateFrom = document.getElementById('date_from').value;
    const dateTo = document.getElementById('date_to').value;
    const paymentMethod = document.getElementById('payment_method').value;

    if (dateFrom) params.set('date_from', dateFrom);
    if (dateTo) params.set('date_to', dateTo);
    if (paymentMethod !== 'all') params.set('payment_method', paymentMethod);

    const baseUrls = {
        csv: "{{ route('pemilik.exports.csv') }}",
        excel: "{{ route('pemilik.exports.excel') }}",
        pdf: "{{ route('pemilik.exports.pdf') }}"
    };

    const exportPath = new URL(baseUrls[format], window.location.origin).pathname;
    const fullUrl = window.location.origin + exportPath + '?' + params.toString();

    const originalHtml = btn ? btn.innerHTML : null;
    if (btn) { btn.disabled = true; btn.innerHTML = 'Memproses...'; }

    try {
        const res = await fetch(fullUrl, { credentials: 'same-origin' });
        const contentType = res.headers.get('Content-Type') || '';

        if (!res.ok || contentType.indexOf('text/html') !== -1) {
            let msg = 'Gagal mengekspor data (status ' + res.status + ').';
            if (contentType.indexOf('text/html') !== -1) {
                msg = 'Sesi telah berakhir. Silakan muat ulang halaman lalu coba lagi.';
            }
            alert(msg);
            return;
        }

        const blob = await res.blob();
        let filename = 'laporan.' + format;
        const disposition = res.headers.get('Content-Disposition') || '';
        const match = disposition.match(/filename\*?=(?:UTF-8'')?"?([^";]+)"?/i);
        if (match) filename = decodeURIComponent(match[1]);

        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        a.remove();
        URL.revokeObjectURL(a.href);
    } catch (e) {
        alert('Terjadi kesalahan saat mengekspor: ' + e.message);
    } finally {
        if (btn) { btn.disabled = false; btn.innerHTML = originalHtml; }
    }
}

function resetFilters() {
    document.getElementById('date_from').value = '';
    document.getElementById('date_to').value = '';
    document.getElementById('payment_method').value = 'all';
}
</script>
@endpush
