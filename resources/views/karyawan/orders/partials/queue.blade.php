<div data-order-queue-revision="{{ $revision }}" data-revision-url="{{ route('karyawan.orders.revision') }}" data-snapshot-url="{{ route('karyawan.orders.snapshot', array_filter(['status' => $status, 'page' => $orders->currentPage() > 1 ? $orders->currentPage() : null])) }}">
{{-- Status Tabs --}}
<div data-queue-filters class="flex flex-wrap items-center gap-3 mb-6">
    <a href="{{ route('karyawan.orders.index') }}" class="px-4 py-2 text-xs font-semibold rounded-xl border transition-all {{ !$status ? 'bg-espresso text-cream border-espresso' : 'bg-white text-caramel border-latte/50 hover:border-caramel' }}">
        Semua
    </a>
    <a href="{{ route('karyawan.orders.index', ['status' => 'menunggu_verifikasi']) }}" class="px-4 py-2 text-xs font-semibold rounded-xl border transition-all {{ $status === 'menunggu_verifikasi' ? 'bg-orange-500 text-white border-orange-500' : 'bg-white text-caramel border-latte/50 hover:border-caramel' }}">
        Verifikasi ({{ $counts['menunggu_verifikasi'] }})
    </a>
    <a href="{{ route('karyawan.orders.index', ['status' => 'antrian_baru']) }}" class="px-4 py-2 text-xs font-semibold rounded-xl border transition-all {{ $status === 'antrian_baru' ? 'bg-amber-500 text-white border-amber-500' : 'bg-white text-caramel border-latte/50 hover:border-caramel' }}">
        Antrean Baru ({{ $counts['antrian_baru'] }})
    </a>
    <a href="{{ route('karyawan.orders.index', ['status' => 'sedang_dibuat']) }}" class="px-4 py-2 text-xs font-semibold rounded-xl border transition-all {{ $status === 'sedang_dibuat' ? 'bg-blue-500 text-white border-blue-500' : 'bg-white text-caramel border-latte/50 hover:border-caramel' }}">
        Sedang Dibuat ({{ $counts['sedang_dibuat'] }})
    </a>
    <a href="{{ route('karyawan.orders.index', ['status' => 'selesai']) }}" class="px-4 py-2 text-xs font-semibold rounded-xl border transition-all {{ $status === 'selesai' ? 'bg-green-500 text-white border-green-500' : 'bg-white text-caramel border-latte/50 hover:border-caramel' }}">
        Siap Diambil ({{ $counts['selesai'] }})
    </a>
</div>

{{-- Orders List --}}
<div data-queue-list class="space-y-4">
    @forelse ($orders as $order)
        @php $canHandleOrder = $order->cashier_id === null || $order->cashier_id === auth()->id(); @endphp
        <div data-order-id="{{ $order->id }}" class="bg-white rounded-2xl shadow-sm border border-latte/50 p-5 hover:shadow-md transition-all duration-300">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="text-sm font-bold text-espresso">{{ $order->order_number }}</span>
                        <span class="px-2.5 py-0.5 rounded-full text-[0.6rem] font-bold uppercase tracking-wider {{ $order->status_color }}">{{ $order->status_label }}</span>
                        <span class="px-2 py-0.5 rounded-full text-[0.6rem] font-bold uppercase tracking-wider bg-latte/40 text-espresso">{{ $order->payment_method }}</span>
                        <span class="px-2 py-0.5 rounded-full text-[0.6rem] font-bold uppercase tracking-wider {{ $order->isMobileOrder() ? 'bg-sky-50 text-sky-700' : 'bg-stone-100 text-stone-700' }}">{{ $order->order_source_label }}</span>
                    </div>
                    <p class="text-xs text-caramel-dark"><span class="font-medium text-espresso">{{ $order->customer_name }}</span> &middot; {{ $order->created_at->format('d/m H:i') }} &middot; {{ $order->formatted_total }}</p>
                    @if ($order->cashier)
                        <p class="text-[0.65rem] text-caramel mt-1">Ditangani oleh {{ $order->cashier->name }}</p>
                    @endif
                    <div class="flex flex-wrap gap-1.5 mt-2">
                        @foreach ($order->items as $item)
                            <span class="text-[0.6rem] bg-latte/30 text-espresso px-2 py-0.5 rounded-md">{{ $item->product_name }} x{{ $item->quantity }}</span>
                        @endforeach
                    </div>
                </div>
                <div class="flex flex-wrap items-center justify-end gap-2 shrink-0">
                    @if ($order->isMobileOrder() && $order->status === 'selesai')
                        <span class="px-3 py-2 text-xs font-semibold bg-green-50 text-green-700 rounded-lg">Menunggu pelanggan mengambil</span>
                    @endif

                    @if ($canHandleOrder)
                        @if ($order->status === 'menunggu_verifikasi')
                            {{-- View Payment Proof & Verify --}}
                            @if ($order->payment_proof)
                                <button onclick='viewProof(@json(route('karyawan.orders.payment-proof', $order)), @json($order->order_number), @json($order->customer_name), @json($order->formatted_total))' class="px-3 py-2 text-xs font-semibold bg-orange-50 text-orange-600 hover:bg-orange-100 rounded-lg transition flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    Lihat Bukti
                                </button>
                            @endif
                            <form method="POST" action="{{ route('karyawan.orders.verify', $order) }}">
                                @csrf @method('PATCH')
                                <button class="px-3 py-2 text-xs font-semibold bg-green-50 text-green-600 hover:bg-green-100 rounded-lg transition flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Validasi & Proses
                                </button>
                            </form>
                        @elseif ($order->status === 'antrian_baru')
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
                    @else
                        <span class="px-3 py-2 text-xs font-semibold bg-latte/30 text-caramel-dark rounded-lg">Ditangani karyawan lain</span>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div data-queue-empty class="bg-white rounded-2xl shadow-sm border border-latte/50 p-12 text-center">
            <div class="w-16 h-16 bg-latte/30 rounded-2xl flex items-center justify-center mb-4 mx-auto">
                <svg class="w-8 h-8 text-caramel" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/></svg>
            </div>
            <p class="text-sm text-caramel-dark font-medium">Tidak ada pesanan aktif</p>
            <p class="text-xs text-caramel mt-1">Pesanan akan hilang dari antrean hanya setelah mencapai status akhir.</p>
        </div>
    @endforelse
</div>

<div data-queue-pagination>
@if ($orders->hasPages())
    <div class="mt-6">{{ $orders->links() }}</div>
@endif
</div>
</div>
