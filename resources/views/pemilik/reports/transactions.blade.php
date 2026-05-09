@extends('layouts.pemilik')
@section('title', 'Riwayat Transaksi')
@section('page-title', 'Riwayat Transaksi')
@section('page-description', 'Semua transaksi yang tercatat')

@section('content')
{{-- Stats Cards --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4 mb-6">
    <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-3 sm:p-4">
        <p class="text-[0.6rem] sm:text-[0.65rem] text-caramel font-semibold uppercase tracking-wider mb-1">Total Transaksi</p>
        <p class="text-lg sm:text-xl font-bold text-espresso">{{ number_format($stats['total_transactions']) }}</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-3 sm:p-4">
        <p class="text-[0.6rem] sm:text-[0.65rem] text-caramel font-semibold uppercase tracking-wider mb-1">Total Pendapatan</p>
        <p class="text-lg sm:text-xl font-bold text-espresso">Rp {{ number_format($stats['total_revenue'], 0, ',', '.') }}</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-3 sm:p-4">
        <p class="text-[0.6rem] sm:text-[0.65rem] text-caramel font-semibold uppercase tracking-wider mb-1">Rata-rata</p>
        <p class="text-lg sm:text-xl font-bold text-espresso">Rp {{ number_format($stats['avg_transaction'], 0, ',', '.') }}</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-3 sm:p-4">
        <p class="text-[0.6rem] sm:text-[0.65rem] text-caramel font-semibold uppercase tracking-wider mb-1">Hari Ini</p>
        <p class="text-lg sm:text-xl font-bold text-espresso">{{ $stats['today_count'] }}</p>
    </div>
</div>

{{-- Filters --}}
<div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-4 sm:p-5 mb-6">
    <form method="GET" action="{{ route('pemilik.reports.transactions') }}">
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-3">
            <div class="col-span-2 sm:col-span-3">
                <label class="block text-xs font-semibold text-espresso mb-1">Cari</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="No. pesanan / nama pelanggan..." class="w-full px-3 py-2 text-sm border border-latte/60 rounded-xl focus:ring-2 focus:ring-caramel/30 focus:border-caramel outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-espresso mb-1">Dari</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full px-3 py-2 text-sm border border-latte/60 rounded-xl focus:ring-2 focus:ring-caramel/30 focus:border-caramel outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-espresso mb-1">Sampai</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full px-3 py-2 text-sm border border-latte/60 rounded-xl focus:ring-2 focus:ring-caramel/30 focus:border-caramel outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-espresso mb-1">Pembayaran</label>
                <select name="payment_method" class="w-full px-3 py-2 text-sm border border-latte/60 rounded-xl focus:ring-2 focus:ring-caramel/30 focus:border-caramel outline-none">
                    <option value="">Semua</option>
                    <option value="cash" {{ request('payment_method') === 'cash' ? 'selected' : '' }}>Cash</option>
                    <option value="qris" {{ request('payment_method') === 'qris' ? 'selected' : '' }}>QRIS</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-espresso mb-1">Status</label>
                <select name="status" class="w-full px-3 py-2 text-sm border border-latte/60 rounded-xl focus:ring-2 focus:ring-caramel/30 focus:border-caramel outline-none">
                    <option value="">Semua</option>
                    <option value="antrian_baru" {{ request('status') === 'antrian_baru' ? 'selected' : '' }}>Antrean Baru</option>
                    <option value="sedang_dibuat" {{ request('status') === 'sedang_dibuat' ? 'selected' : '' }}>Sedang Dibuat</option>
                    <option value="selesai" {{ request('status') === 'selesai' ? 'selected' : '' }}>Selesai</option>
                </select>
            </div>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="flex-1 sm:flex-none px-4 py-2 text-sm font-semibold bg-espresso text-cream rounded-xl hover:bg-espresso-light transition">Filter</button>
            <a href="{{ route('pemilik.reports.transactions') }}" class="flex-1 sm:flex-none px-4 py-2 text-sm font-medium text-caramel hover:text-espresso border border-latte/60 rounded-xl transition text-center">Atur Ulang</a>
        </div>
    </form>
</div>

{{-- Transaction Cards --}}
<div class="space-y-3">
    @forelse ($orders as $order)
    <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-4 sm:p-5 hover:shadow-md transition-all duration-300">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="flex-1 min-w-0">
                <div class="flex flex-wrap items-center gap-2 mb-2">
                    <span class="text-sm font-bold text-espresso">{{ $order->order_number }}</span>
                    <span class="px-2.5 py-0.5 rounded-full text-[0.6rem] font-bold uppercase tracking-wider {{ $order->status_color }}">{{ $order->status_label }}</span>
                    <span class="px-2 py-0.5 rounded-full text-[0.6rem] font-bold uppercase tracking-wider bg-latte/40 text-espresso">{{ $order->payment_method }}</span>
                </div>
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-caramel-dark mb-2">
                    <span><span class="font-medium text-espresso">{{ $order->customer_name }}</span></span>
                    <span>Kasir: {{ $order->user->name ?? '-' }}</span>
                    <span>{{ $order->created_at->format('d/m/Y H:i') }}</span>
                </div>
                <div class="flex flex-wrap gap-1.5">
                    @foreach ($order->items->take(3) as $item)
                        <span class="text-[0.6rem] bg-latte/30 text-espresso px-2 py-0.5 rounded-md">{{ $item->product_name }} x{{ $item->quantity }}</span>
                    @endforeach
                    @if ($order->items->count() > 3)
                        <span class="text-[0.6rem] bg-caramel/20 text-espresso px-2 py-0.5 rounded-md">+{{ $order->items->count() - 3 }} lainnya</span>
                    @endif
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <span class="text-sm font-bold text-espresso">{{ $order->formatted_total }}</span>
                <button onclick="viewReceipt({{ $order->id }})" class="px-3 py-2 text-xs font-semibold bg-espresso/5 text-espresso hover:bg-espresso/15 rounded-lg transition">Lihat Struk</button>
            </div>
        </div>
    </div>
    @empty
    <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-12 text-center">
        <div class="w-14 h-14 bg-latte/30 rounded-2xl flex items-center justify-center mb-3 mx-auto">
            <svg class="w-7 h-7 text-caramel" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
        </div>
        <p class="text-sm text-caramel-dark font-medium">Belum ada transaksi</p>
    </div>
    @endforelse
</div>

@if ($orders->hasPages())
    <div class="mt-6">{{ $orders->links() }}</div>
@endif

@endsection

@push('modals')
{{-- Receipt Modal --}}
<div id="receipt-modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closeReceipt()"></div>
    <div class="absolute inset-0 overflow-y-auto flex items-center justify-center p-4 py-16">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm animate-fade-in my-auto">
            <div id="receipt-content" class="p-6">
                <div class="text-center border-b border-dashed border-latte/60 pb-4 mb-4">
                    <h3 class="text-base font-bold text-espresso font-sans">Ciks Coffee</h3>
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
            <div class="px-6 pb-6 flex gap-3">
                <button onclick="printReceipt()" class="flex-1 bg-espresso hover:bg-espresso-light text-cream text-sm font-semibold py-3 rounded-xl transition-all flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z"/></svg>
                    Cetak Struk
                </button>
                <button onclick="closeReceipt()" class="px-4 py-3 text-sm font-medium text-caramel hover:text-espresso rounded-xl border border-latte/60 hover:border-caramel transition">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endpush

@push('scripts')
<script>
    function viewReceipt(orderId) {
        fetch(`/pemilik/reports/transactions/${orderId}/receipt`).then(r => r.json()).then(data => {
            document.getElementById('rcpt-number').textContent = data.order_number;
            document.getElementById('rcpt-customer').textContent = data.customer_name;
            document.getElementById('rcpt-cashier').textContent = data.cashier;
            document.getElementById('rcpt-time').textContent = data.paid_at || data.created_at;
            document.getElementById('rcpt-payment').textContent = data.payment_method;
            document.getElementById('rcpt-total').textContent = data.formatted_total;
            document.getElementById('rcpt-status').textContent = data.status_label;
            let itemsHtml = '';
            data.items.forEach(i => {
                itemsHtml += `<div class="flex justify-between text-xs"><span class="text-espresso">${i.product_name} x${i.quantity}</span><span class="font-medium text-espresso">Rp ${parseFloat(i.subtotal).toLocaleString('id-ID')}</span></div>`;
            });
            document.getElementById('rcpt-items').innerHTML = itemsHtml;
            if (data.payment_method === 'cash' && data.cash_received) {
                document.getElementById('rcpt-cash-row').classList.remove('hidden');
                document.getElementById('rcpt-change-row').classList.remove('hidden');
                document.getElementById('rcpt-cash').textContent = 'Rp ' + parseFloat(data.cash_received).toLocaleString('id-ID');
                document.getElementById('rcpt-change').textContent = 'Rp ' + parseFloat(data.change_amount).toLocaleString('id-ID');
            } else {
                document.getElementById('rcpt-cash-row').classList.add('hidden');
                document.getElementById('rcpt-change-row').classList.add('hidden');
            }
            document.getElementById('receipt-modal').classList.remove('hidden');
        });
    }
    function closeReceipt() { document.getElementById('receipt-modal').classList.add('hidden'); }
    function printReceipt() {
        const content = document.getElementById('receipt-content').innerHTML;
        const w = window.open('', '_blank', 'width=320,height=600');
        w.document.write(`<html><head><title>Struk</title><style>body{font-family:monospace,sans-serif;font-size:12px;padding:10px;max-width:280px;margin:0 auto}h3{margin:0;font-size:16px;text-align:center}.flex{display:flex;justify-content:space-between}.border-t,.border-b{border-top:1px dashed #ccc;padding-top:8px;margin-top:8px}.mb-4{margin-bottom:12px}.font-bold{font-weight:bold}.space-y-1>*+*{margin-top:4px}.space-y-2>*+*{margin-top:6px}span{display:inline-block}@media print{body{padding:0}}</style></head><body>${content}<div class="text-center border-t" style="margin-top:12px;padding-top:8px;text-align:center"><small>*** Terima Kasih ***</small></div></body></html>`);
        w.document.close(); w.focus(); w.print(); w.close();
    }
</script>
@endpush
