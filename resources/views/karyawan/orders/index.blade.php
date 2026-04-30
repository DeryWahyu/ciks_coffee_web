@extends('layouts.karyawan')
@section('title', 'Antrean Pesanan')
@section('page-title', 'Antrean Pesanan')
@section('page-description', 'Kelola status pesanan hari ini')

@section('content')
{{-- Status Tabs --}}
<div class="flex flex-wrap items-center gap-3 mb-6">
    <a href="{{ route('karyawan.orders.index') }}" class="px-4 py-2 text-xs font-semibold rounded-xl border transition-all {{ !$status ? 'bg-espresso text-cream border-espresso' : 'bg-white text-caramel border-latte/50 hover:border-caramel' }}">
        Semua
    </a>
    <a href="{{ route('karyawan.orders.index', ['status' => 'antrian_baru']) }}" class="px-4 py-2 text-xs font-semibold rounded-xl border transition-all {{ $status === 'antrian_baru' ? 'bg-amber-500 text-white border-amber-500' : 'bg-white text-caramel border-latte/50 hover:border-caramel' }}">
        Antrean Baru ({{ $counts['antrian_baru'] }})
    </a>
    <a href="{{ route('karyawan.orders.index', ['status' => 'sedang_dibuat']) }}" class="px-4 py-2 text-xs font-semibold rounded-xl border transition-all {{ $status === 'sedang_dibuat' ? 'bg-blue-500 text-white border-blue-500' : 'bg-white text-caramel border-latte/50 hover:border-caramel' }}">
        Sedang Dibuat ({{ $counts['sedang_dibuat'] }})
    </a>
    <a href="{{ route('karyawan.orders.index', ['status' => 'selesai']) }}" class="px-4 py-2 text-xs font-semibold rounded-xl border transition-all {{ $status === 'selesai' ? 'bg-green-500 text-white border-green-500' : 'bg-white text-caramel border-latte/50 hover:border-caramel' }}">
        Selesai ({{ $counts['selesai'] }})
    </a>
</div>

{{-- Orders List --}}
<div class="space-y-4">
    @forelse ($orders as $order)
        <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-5 hover:shadow-md transition-all duration-300">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="text-sm font-bold text-espresso">{{ $order->order_number }}</span>
                        <span class="px-2.5 py-0.5 rounded-full text-[0.6rem] font-bold uppercase tracking-wider {{ $order->status_color }}">{{ $order->status_label }}</span>
                        <span class="px-2 py-0.5 rounded-full text-[0.6rem] font-bold uppercase tracking-wider bg-latte/40 text-espresso">{{ $order->payment_method }}</span>
                    </div>
                    <p class="text-xs text-caramel-dark"><span class="font-medium text-espresso">{{ $order->customer_name }}</span> · {{ $order->paid_at?->format('H:i') }} · {{ $order->formatted_total }}</p>
                    <div class="flex flex-wrap gap-1.5 mt-2">
                        @foreach ($order->items as $item)
                            <span class="text-[0.6rem] bg-latte/30 text-espresso px-2 py-0.5 rounded-md">{{ $item->product_name }} x{{ $item->quantity }}</span>
                        @endforeach
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    @if ($order->status === 'antrian_baru')
                        <form method="POST" action="{{ route('karyawan.orders.update-status', $order) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="sedang_dibuat">
                            <button class="px-3 py-2 text-xs font-semibold bg-blue-50 text-blue-600 hover:bg-blue-100 rounded-lg transition">Proses</button>
                        </form>
                    @elseif ($order->status === 'sedang_dibuat')
                        <form method="POST" action="{{ route('karyawan.orders.update-status', $order) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="selesai">
                            <button class="px-3 py-2 text-xs font-semibold bg-green-50 text-green-600 hover:bg-green-100 rounded-lg transition">Selesai</button>
                        </form>
                    @endif
                    <button onclick="viewReceipt({{ $order->id }})" class="px-3 py-2 text-xs font-semibold bg-espresso/5 text-espresso hover:bg-espresso/15 rounded-lg transition">Struk</button>
                </div>
            </div>
        </div>
    @empty
        <div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-12 text-center">
            <div class="w-16 h-16 bg-latte/30 rounded-2xl flex items-center justify-center mb-4 mx-auto">
                <svg class="w-8 h-8 text-caramel" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/></svg>
            </div>
            <p class="text-sm text-caramel-dark font-medium">Belum ada pesanan hari ini</p>
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
        fetch(`/karyawan/orders/${orderId}/receipt`).then(r => r.json()).then(data => {
            document.getElementById('rcpt-number').textContent = data.order_number;
            document.getElementById('rcpt-customer').textContent = data.customer_name;
            document.getElementById('rcpt-cashier').textContent = data.cashier;
            document.getElementById('rcpt-time').textContent = data.paid_at;
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
