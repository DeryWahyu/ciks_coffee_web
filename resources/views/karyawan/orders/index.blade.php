@extends('layouts.karyawan')
@section('title', 'Antrean Pesanan')
@section('page-title', 'Antrean Pesanan')
@section('page-description', 'Pesanan aktif tetap tampil sampai proses benar-benar tuntas')

@section('content')
<p id="order-queue-sync-status" role="status" aria-live="polite" class="sr-only"></p>
@include('karyawan.orders.partials.queue')
@endsection

@push('modals')
{{-- Payment Proof Modal --}}
<div id="proof-modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeProof()"></div>
    <div class="absolute inset-0 overflow-y-auto flex items-center justify-center p-4 py-16">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md animate-fade-in my-auto">
            <div class="flex items-center justify-between px-6 pt-6 pb-4 border-b border-latte/40">
                <div>
                    <h3 class="text-base font-bold text-espresso">Bukti Pembayaran QRIS</h3>
                    <p id="proof-order-info" class="text-xs text-caramel mt-0.5"></p>
                </div>
                <button onclick="closeProof()" class="p-1 rounded-lg hover:bg-latte/30 text-caramel transition"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <div class="p-6">
                <div class="bg-cream-light rounded-xl p-3 border border-latte/40 mb-4">
                    <img id="proof-image" src="" alt="Bukti Pembayaran" class="w-full rounded-lg object-contain max-h-[400px]">
                </div>
                <div class="grid grid-cols-2 gap-3 text-xs">
                    <div class="bg-latte/20 rounded-lg p-3">
                        <p class="text-caramel font-medium mb-0.5">Pelanggan</p>
                        <p id="proof-customer" class="text-espresso font-bold"></p>
                    </div>
                    <div class="bg-latte/20 rounded-lg p-3">
                        <p class="text-caramel font-medium mb-0.5">Total</p>
                        <p id="proof-total" class="text-espresso font-bold"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Receipt Modal --}}
<div id="receipt-modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closeReceipt()"></div>
    <div class="absolute inset-0 overflow-y-auto flex items-center justify-center p-4 py-16">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm animate-fade-in my-auto">
            <div id="receipt-content" class="p-6">
                <div class="text-center border-b border-dashed border-latte/60 pb-4 mb-4">
                    <x-brand-logo style="display:block;width:40px;height:40px;margin:0 auto 8px;" />
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
    // Payment Proof Modal
    function viewProof(imageUrl, orderNumber, customerName, total) {
        document.getElementById('proof-image').src = imageUrl;
        document.getElementById('proof-order-info').textContent = orderNumber;
        document.getElementById('proof-customer').textContent = customerName;
        document.getElementById('proof-total').textContent = total;
        document.getElementById('proof-modal').classList.remove('hidden');
    }
    function closeProof() { document.getElementById('proof-modal').classList.add('hidden'); }

    // Receipt Modal
    function formatCurrency(value) {
        return `Rp ${Number(value ?? 0).toLocaleString('id-ID')}`;
    }

    function renderReceiptItems(items) {
        const container = document.getElementById('rcpt-items');
        container.replaceChildren();

        (Array.isArray(items) ? items : []).forEach((item) => {
            const row = document.createElement('div');
            row.className = 'flex justify-between gap-4 text-xs';

            const name = document.createElement('span');
            name.className = 'text-espresso';
            name.textContent = `${item.product_name ?? '-'} x${item.quantity ?? 0}`;

            const subtotal = document.createElement('span');
            subtotal.className = 'font-medium text-espresso text-right';
            subtotal.textContent = formatCurrency(item.subtotal);

            row.append(name, subtotal);
            container.append(row);
        });
    }

    function viewReceipt(orderId) {
        fetch(`/karyawan/orders/${orderId}/receipt`).then(r => r.json()).then(data => {
            document.getElementById('rcpt-number').textContent = data.order_number;
            document.getElementById('rcpt-customer').textContent = data.customer_name;
            document.getElementById('rcpt-cashier').textContent = data.cashier;
            document.getElementById('rcpt-time').textContent = data.paid_at;
            document.getElementById('rcpt-payment').textContent = data.payment_method;
            document.getElementById('rcpt-total').textContent = data.formatted_total;
            document.getElementById('rcpt-status').textContent = data.status_label;
            renderReceiptItems(data.items);
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
        const w = window.open('', '_blank', 'width=320,height=600');
        if (!w) {
            return;
        }

        w.document.write('<html><head><title>Struk</title><style>body{font-family:monospace,sans-serif;font-size:12px;padding:10px;max-width:280px;margin:0 auto}h3{margin:0;font-size:16px;text-align:center}.flex{display:flex;justify-content:space-between}.border-t,.border-b{border-top:1px dashed #ccc;padding-top:8px;margin-top:8px}.mb-4{margin-bottom:12px}.font-bold{font-weight:bold}.space-y-1>*+*{margin-top:4px}.space-y-2>*+*{margin-top:6px}span{display:inline-block}@media print{body{padding:0}}</style></head><body></body></html>');
        w.document.body.append(document.getElementById('receipt-content').cloneNode(true));

        const footer = w.document.createElement('div');
        footer.className = 'text-center border-t';
        footer.style.cssText = 'margin-top:12px;padding-top:8px;text-align:center';
        footer.textContent = '*** Terima Kasih ***';
        w.document.body.append(footer);
        w.document.close();
        Promise.all(Array.from(w.document.images, image => image.decode().catch(() => {}))).then(() => {
            w.focus();
            w.print();
            w.close();
        });
    }
</script>
@endpush
