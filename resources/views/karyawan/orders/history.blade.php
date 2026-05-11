@extends('layouts.karyawan')
@section('title', 'Riwayat Transaksi')
@section('page-title', 'Riwayat Transaksi')
@section('page-description', 'Semua transaksi yang tercatat')

@section('content')


{{-- Filters --}}
<div class="bg-white rounded-2xl shadow-sm border border-latte/50 p-4 sm:p-5 mb-6">
    <form method="GET" action="{{ route('karyawan.orders.history') }}">
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
            <a href="{{ route('karyawan.orders.history') }}" class="flex-1 sm:flex-none px-4 py-2 text-sm font-medium text-caramel hover:text-espresso border border-latte/60 rounded-xl transition text-center">Reset</a>
        </div>
    </form>
</div>

{{-- Transaction Cards (always visible, works on all screens) --}}
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
                <button onclick="viewDetail({{ $order->id }})" class="p-2 rounded-lg bg-espresso/5 text-espresso hover:bg-espresso/15 transition" title="Lihat Detail">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </button>

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
{{-- Detail Modal --}}
<div id="detail-modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closeDetail()"></div>
    <div class="absolute inset-0 overflow-y-auto flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md animate-fade-in my-auto">
            <div class="flex items-center justify-between px-5 sm:px-6 pt-5 sm:pt-6 pb-4 border-b border-latte/40">
                <h3 class="text-base font-bold text-espresso">Detail Transaksi</h3>
                <button onclick="closeDetail()" class="p-1 rounded-lg hover:bg-latte/30 text-caramel transition"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <div id="detail-content" class="p-5 sm:p-6">
                <div class="text-center py-8"><div class="animate-spin w-6 h-6 border-2 border-espresso border-t-transparent rounded-full mx-auto"></div><p class="text-xs text-caramel mt-2">Memuat...</p></div>
            </div>
        </div>
    </div>
</div>


@endpush

@push('scripts')
<script>
    function viewDetail(id) {
        document.getElementById('detail-modal').classList.remove('hidden');
        document.getElementById('detail-content').innerHTML = '<div class="text-center py-8"><div class="animate-spin w-6 h-6 border-2 border-espresso border-t-transparent rounded-full mx-auto"></div><p class="text-xs text-caramel mt-2">Memuat...</p></div>';
        fetch(`/karyawan/orders/${id}/detail`).then(r => r.json()).then(d => {
            let itemsHtml = d.items.map(i => `<div class="flex justify-between text-xs py-1"><span class="text-espresso">${i.product_name} x${i.quantity}</span><span class="font-medium text-espresso">Rp ${parseFloat(i.subtotal).toLocaleString('id-ID')}</span></div>`).join('');
            let cashHtml = '';
            if (d.payment_method === 'cash' && d.cash_received) {
                cashHtml = `<div class="flex justify-between text-xs"><span class="text-caramel">Tunai</span><span class="text-espresso">Rp ${parseFloat(d.cash_received).toLocaleString('id-ID')}</span></div>
                <div class="flex justify-between text-xs"><span class="text-caramel">Kembalian</span><span class="font-medium text-green-600">Rp ${parseFloat(d.change_amount).toLocaleString('id-ID')}</span></div>`;
            }
            document.getElementById('detail-content').innerHTML = `
                <div class="space-y-2 text-xs mb-4">
                    <div class="flex justify-between"><span class="text-caramel">No. Pesanan</span><span class="font-bold text-espresso">${d.order_number}</span></div>
                    <div class="flex justify-between"><span class="text-caramel">Pelanggan</span><span class="font-medium text-espresso">${d.customer_name}</span></div>
                    <div class="flex justify-between"><span class="text-caramel">Kasir</span><span class="text-espresso">${d.cashier}</span></div>
                    <div class="flex justify-between"><span class="text-caramel">Waktu</span><span class="text-espresso">${d.paid_at || d.created_at}</span></div>
                    <div class="flex justify-between"><span class="text-caramel">Pembayaran</span><span class="font-medium text-espresso uppercase">${d.payment_method}</span></div>
                    <div class="flex justify-between items-center"><span class="text-caramel">Status</span><span class="px-2 py-0.5 rounded-full text-[0.6rem] font-bold uppercase ${d.status_color}">${d.status_label}</span></div>
                </div>
                <div class="border-t border-dashed border-latte/60 pt-3 mb-3">${itemsHtml}</div>
                <div class="border-t border-dashed border-latte/60 pt-3 space-y-1.5">
                    <div class="flex justify-between text-sm"><span class="font-bold text-espresso">Total</span><span class="font-bold text-espresso">${d.formatted_total}</span></div>
                    ${cashHtml}
                </div>`;
        });
    }
    function closeDetail() { document.getElementById('detail-modal').classList.add('hidden'); }
</script>
@endpush
