@extends('layouts.karyawan')
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
                <button onclick="confirmDelete({{ $order->id }}, '{{ $order->order_number }}')" class="p-2 rounded-lg bg-red-50 text-red-500 hover:bg-red-100 transition" title="Hapus">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
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

<form id="delete-form" method="POST" class="hidden">@csrf @method('DELETE')</form>
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

{{-- Delete Confirmation Modal --}}
<div id="delete-modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closeDeleteModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm animate-fade-in p-6 text-center">
            <div class="w-14 h-14 bg-red-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
            </div>
            <h3 class="text-base font-bold text-espresso mb-1">Hapus Transaksi?</h3>
            <p class="text-sm text-caramel-dark mb-5">Transaksi <span id="delete-order-number" class="font-bold text-espresso"></span> akan dihapus permanen.</p>
            <div class="flex gap-3">
                <button onclick="closeDeleteModal()" class="flex-1 px-4 py-2.5 text-sm font-medium text-caramel border border-latte/60 rounded-xl hover:border-caramel transition">Batal</button>
                <button onclick="executeDelete()" class="flex-1 px-4 py-2.5 text-sm font-semibold bg-red-500 text-white rounded-xl hover:bg-red-600 transition">Hapus</button>
            </div>
        </div>
    </div>
</div>
@endpush

@push('scripts')
<script>
    let deleteOrderId = null;

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

    function confirmDelete(id, orderNum) {
        deleteOrderId = id;
        document.getElementById('delete-order-number').textContent = '#' + orderNum;
        document.getElementById('delete-modal').classList.remove('hidden');
    }
    function closeDeleteModal() { document.getElementById('delete-modal').classList.add('hidden'); deleteOrderId = null; }
    function executeDelete() {
        if (!deleteOrderId) return;
        const form = document.getElementById('delete-form');
        form.action = `/karyawan/orders/${deleteOrderId}`;
        form.submit();
    }
</script>
@endpush
