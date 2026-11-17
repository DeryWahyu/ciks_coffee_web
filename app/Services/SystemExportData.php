<?php

namespace App\Services;

use App\Models\Category;
use App\Models\CoffeeTable;
use App\Models\FloorLayout;
use App\Models\Ingredient;
use App\Models\MobileCheckout;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShopSetting;
use App\Models\TableStatusHistory;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SystemExportData
{
    public function orders(array $filters, bool $transactions = false): Builder
    {
        $query = Order::query();
        if ($transactions) {
            $query->visibleInTransactionHistory();
        }
        if (! empty($filters['date_from'])) {
            $query->where('orders.created_at', '>=', $filters['date_from'].' 00:00:00');
        }
        if (! empty($filters['date_to'])) {
            $query->where('orders.created_at', '<', CarbonImmutable::parse($filters['date_to'])->addDay()->startOfDay());
        }
        if (! empty($filters['payment_method']) && $filters['payment_method'] !== 'all') {
            $query->where('orders.payment_method', $filters['payment_method']);
        }

        return $query;
    }

    /** Explicit business columns keep credentials, sessions and device tokens out of exports. */
    public function sections(array $filters): array
    {
        $checkouts = function () use ($filters) {
            $query = MobileCheckout::query();
            if (! empty($filters['date_from'])) {
                $query->where('created_at', '>=', $filters['date_from'].' 00:00:00');
            }
            if (! empty($filters['date_to'])) {
                $query->where('created_at', '<', CarbonImmutable::parse($filters['date_to'])->addDay()->startOfDay());
            }
            if (($filters['payment_method'] ?? 'all') === 'cash') {
                $query->whereRaw('1 = 0');
            }

            return $query;
        };
        $orders = fn () => $this->orders($filters);
        $transactions = fn () => $this->orders($filters, true);
        $orderColumns = ['ID Pesanan', 'No. Order', 'Tanggal', 'ID Pemesan', 'Pelanggan', 'Asal', 'ID Kasir', 'Kasir', 'Metode Bayar', 'Status', 'Masuk Riwayat', 'Total', 'Uang Diterima', 'Kembalian', 'Dibayar Pada', 'Bukti Pembayaran', 'Diperbarui Pada', 'Kendala Pembayaran', 'Catatan Penyelesaian', 'Diselesaikan Pada', 'ID Petugas Penyelesaian'];
        $orderRows = fn ($query) => $query->with(['user:id,name,role', 'cashier:id,name'])->lazyById(250)->map(fn ($o) => [
            $o->id, $o->order_number, $o->created_at?->toDateTimeString(), $o->user_id,
            $o->customer_name, $o->order_source_label, $o->cashier_id, $o->cashier?->name,
            $o->payment_method, $o->status, $o->isPosOrder() || $o->status === 'diambil' ? 'Ya' : 'Belum',
            $o->total, $o->cash_received, $o->change_amount, $o->paid_at?->toDateTimeString(),
            $o->payment_proof, $o->updated_at?->toDateTimeString(),
            $o->payment_review_reason, $o->payment_resolution_note, $o->payment_resolved_at?->toDateTimeString(), $o->payment_resolved_by,
        ]);

        return [
            'ringkasan' => [
                'title' => 'Ringkasan', 'headers' => ['Keterangan', 'Nilai'],
                'rows' => fn () => [
                    ['Sistem', 'Ciks Coffee'], ['Dibuat pada', now()->toDateTimeString()],
                    ['Zona waktu', config('app.timezone')],
                    ['Dari tanggal', $filters['date_from'] ?? 'Semua'], ['Sampai tanggal', $filters['date_to'] ?? 'Semua'],
                    ['Metode bayar', $filters['payment_method'] ?? 'all'],
                    ['Jumlah transaksi', $transactions()->count()], ['Omzet transaksi', (string) $transactions()->sum('total')],
                    ['Jumlah seluruh pesanan', $orders()->count()],
                    ['Pesanan belum masuk riwayat', $orders()->whereNotIn('id', $transactions()->select('orders.id'))->count()],
                    ['Aturan omzet', 'Mengikuti riwayat transaksi web. Pesanan mobile baru dihitung setelah pelanggan mengonfirmasi diambil.'],
                    ['Cakupan filter', 'Tanggal dan metode bayar berlaku untuk pesanan beserta item/pemakaian bahannya. Tanggal juga berlaku untuk riwayat meja. Data master adalah kondisi saat ekspor, termasuk data nonaktif.'],
                    ['Stok produk', 'Jumlah per varian berasal dari resep dan stok bahan saat ekspor. Produk/varian dapat berbagi bahan sehingga jumlahnya tidak boleh dijumlahkan.'],
                    ['Berkas', 'Arsip CSV ZIP menyertakan gambar produk, bukti pembayaran, QRIS dan latar denah yang tersedia. Excel/PDF mencantumkan referensinya.'],
                    ['Kerahasiaan', 'Password, token login/perangkat, kunci API, sesi dan konfigurasi .env tidak diekspor. Ini arsip data bisnis, bukan cadangan untuk pemulihan server.'],
                ],
            ],
            'transaksi' => ['title' => 'Transaksi', 'headers' => $orderColumns, 'rows' => fn () => $orderRows($transactions())],
            'pesanan' => ['title' => 'Seluruh Pesanan', 'headers' => $orderColumns, 'rows' => fn () => $orderRows($orders())],
            'item_pesanan' => [
                'title' => 'Item Pesanan',
                'headers' => ['ID Item', 'ID Pesanan', 'No. Order', 'ID Produk', 'Nama Saat Transaksi', 'Varian', 'Jumlah', 'Harga Satuan', 'Subtotal'],
                'rows' => fn () => DB::table('order_items as i')->join('orders as o', 'o.id', '=', 'i.order_id')
                    ->whereIn('o.id', $orders()->select('orders.id'))
                    ->select('i.id', 'i.order_id', 'o.order_number', 'i.product_id', 'i.product_name', 'i.variant', 'i.quantity', 'i.price', 'i.subtotal')
                    ->orderBy('i.id')->lazy(250)->map(fn ($r) => array_values((array) $r)),
            ],
            'pemakaian_bahan' => [
                'title' => 'Pemakaian Bahan',
                'headers' => ['ID', 'ID Pesanan', 'ID Item', 'ID Bahan', 'Bahan', 'Jumlah Terpakai', 'Satuan'],
                'rows' => fn () => DB::table('order_item_ingredients as used')
                    ->join('order_items as i', 'i.id', '=', 'used.order_item_id')->join('ingredients as b', 'b.id', '=', 'used.ingredient_id')
                    ->join('orders as usage_order', 'usage_order.id', '=', 'i.order_id')
                    ->whereNotNull('usage_order.paid_at')
                    ->whereIn('usage_order.status', ['antrian_baru', 'sedang_dibuat', 'selesai', 'diambil'])
                    ->whereIn('i.order_id', $orders()->select('orders.id'))
                    ->select('used.id', 'i.order_id', 'used.order_item_id', 'used.ingredient_id', 'b.nama_bahan', DB::raw('used.quantity * i.quantity as quantity'), 'b.satuan')
                    ->orderBy('used.id')->lazy(250)->map(fn ($r) => array_values((array) $r)),
            ],
            'sesi_pembayaran' => [
                'title' => 'Sesi Pembayaran', 'headers' => ['ID Sesi', 'ID Pelanggan', 'ID Pesanan', 'Total', 'Status', 'Reservasi Sampai', 'Dibuat Pada'],
                'rows' => fn () => $checkouts()->orderBy('created_at')->lazy(250)->map(fn ($q) => [$q->id, $q->user_id, $q->order_id, $q->total, $q->status, $q->expires_at?->toDateTimeString(), $q->created_at?->toDateTimeString()]),
            ],
            'reservasi_bahan' => [
                'title' => 'Reservasi Bahan', 'headers' => ['ID', 'ID Sesi', 'ID Bahan', 'Jumlah Reservasi', 'Reservasi Sampai', 'Dilepas Pada'],
                'rows' => fn () => DB::table('checkout_reservations')->whereIn('mobile_checkout_id', $checkouts()->select('id'))->orderBy('id')->lazy(250)->map(fn ($r) => [$r->id, $r->mobile_checkout_id, $r->ingredient_id, $r->quantity, $r->expires_at, $r->released_at]),
            ],
            'produk' => [
                'title' => 'Produk',
                'headers' => ['ID Produk', 'ID Kategori', 'Kategori', 'Nama', 'Deskripsi', 'Harga Base/Utama', 'Harga Lite', 'Aktif', 'Stok Default', 'Stok Base', 'Stok Lite', 'Gambar', 'Dibuat Pada', 'Diperbarui Pada'],
                'rows' => fn () => Product::with(['category', 'ingredients'])->lazyById(250)->map(function ($p) {
                    $stock = $p->stockQuantities();

                    return [$p->id, $p->category_id, $p->category?->name, $p->name, $p->description, $p->price, $p->price_lite,
                        $p->is_active ? 'Ya' : 'Tidak', $stock['default'] ?? null, $stock['base'] ?? null, $stock['lite'] ?? null,
                        $p->image, $p->created_at?->toDateTimeString(), $p->updated_at?->toDateTimeString()];
                }),
            ],
            'kategori' => [
                'title' => 'Kategori', 'headers' => ['ID Kategori', 'Nama', 'Slug', 'Aktif', 'Dibuat Pada', 'Diperbarui Pada'],
                'rows' => fn () => Category::orderBy('id')->lazy(250)->map(fn ($c) => [$c->id, $c->name, $c->slug, $c->is_active ? 'Ya' : 'Tidak', $c->created_at?->toDateTimeString(), $c->updated_at?->toDateTimeString()]),
            ],
            'bahan_baku' => [
                'title' => 'Bahan Baku', 'headers' => ['ID Bahan', 'Nama Bahan', 'Satuan', 'Stok', 'Dibuat Pada', 'Diperbarui Pada'],
                'rows' => fn () => Ingredient::orderBy('id')->lazy(250)->map(fn ($b) => [$b->id, $b->nama_bahan, $b->satuan, $b->stok, $b->created_at?->toDateTimeString(), $b->updated_at?->toDateTimeString()]),
            ],
            'resep' => [
                'title' => 'Resep Produk', 'headers' => ['ID', 'ID Produk', 'Produk', 'Varian', 'ID Bahan', 'Bahan', 'Kebutuhan Per Porsi', 'Satuan'],
                'rows' => fn () => DB::table('ingredient_product as r')->join('products as p', 'p.id', '=', 'r.product_id')->join('ingredients as b', 'b.id', '=', 'r.ingredient_id')
                    ->select('r.id', 'r.product_id', 'p.name', 'r.variant', 'r.ingredient_id', 'b.nama_bahan', 'r.quantity', 'b.satuan')
                    ->orderBy('r.id')->lazy(250)->map(fn ($r) => array_values((array) $r)),
            ],
            'akun' => [
                'title' => 'Pelanggan dan Staf', 'headers' => ['ID Akun', 'Nama', 'Email', 'Telepon', 'Role', 'Aktif', 'Email Diverifikasi', 'Dibuat Pada', 'Diperbarui Pada'],
                'rows' => fn () => User::select('id', 'name', 'email', 'phone', 'role', 'is_active', 'email_verified_at', 'created_at', 'updated_at')
                    ->lazyById(250)->map(fn ($u) => [$u->id, $u->name, $u->email, $u->phone, $u->role, $u->is_active ? 'Ya' : 'Tidak', $u->email_verified_at?->toDateTimeString(), $u->created_at?->toDateTimeString(), $u->updated_at?->toDateTimeString()]),
            ],
            'denah' => [
                'title' => 'Denah', 'headers' => ['ID Denah', 'Nama', 'Slug', 'Deskripsi', 'Lebar Kanvas', 'Tinggi Kanvas', 'Objek Denah JSON', 'Aktif', 'Dibuat Pada', 'Diperbarui Pada'],
                'rows' => fn () => FloorLayout::lazyById(250)->map(fn ($l) => [$l->id, $l->name, $l->slug, $l->description, $l->canvas_width, $l->canvas_height,
                    json_encode($l->background_config, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), $l->is_active ? 'Ya' : 'Tidak', $l->created_at?->toDateTimeString(), $l->updated_at?->toDateTimeString()]),
            ],
            'meja' => [
                'title' => 'Meja', 'headers' => ['ID Meja', 'ID Denah', 'Kode', 'Nama', 'Kapasitas', 'Bentuk', 'Posisi X', 'Posisi Y', 'Lebar', 'Tinggi', 'Rotasi', 'Status', 'Catatan', 'Aktif', 'ID Pengubah Status', 'Status Diubah Pada', 'Versi', 'Posisi Kursi JSON'],
                'rows' => fn () => CoffeeTable::lazyById(250)->map(fn ($t) => [$t->id, $t->floor_layout_id, $t->code, $t->name, $t->capacity, $t->shape, $t->position_x, $t->position_y,
                    $t->width, $t->height, $t->rotation, $t->status, $t->status_note, $t->is_active ? 'Ya' : 'Tidak', $t->status_updated_by, $t->status_updated_at?->toDateTimeString(), $t->version, json_encode($t->chair_positions, JSON_THROW_ON_ERROR)]),
            ],
            'riwayat_meja' => [
                'title' => 'Riwayat Status Meja', 'headers' => ['ID', 'ID Meja', 'Kode Meja', 'ID Pengubah', 'Pengubah', 'Status Lama', 'Status Baru', 'Catatan', 'Sumber', 'Tanggal'],
                'rows' => function () use ($filters) {
                    $query = TableStatusHistory::with(['coffeeTable:id,code', 'user:id,name']);
                    if (! empty($filters['date_from'])) {
                        $query->where('created_at', '>=', $filters['date_from'].' 00:00:00');
                    }
                    if (! empty($filters['date_to'])) {
                        $query->where('created_at', '<', CarbonImmutable::parse($filters['date_to'])->addDay()->startOfDay());
                    }

                    return $query->lazyById(250)->map(fn ($h) => [$h->id, $h->coffee_table_id, $h->coffeeTable?->code, $h->user_id, $h->user?->name, $h->old_status, $h->new_status, $h->note, $h->source, $h->created_at?->toDateTimeString()]);
                },
            ],
            'pengaturan' => [
                'title' => 'Pengaturan Toko', 'headers' => ['Pengaturan', 'Nilai'],
                'rows' => fn () => array_merge(
                    ShopSetting::whereIn('key', ['qris_image'])->get(['key', 'value'])->map(fn ($s) => [$s->key, $s->value])->all(),
                    [['Zona waktu', config('app.timezone')], ['Latar denah', config('floor_plan.background.path')], ['Lebar latar', config('floor_plan.background.width')], ['Tinggi latar', config('floor_plan.background.height')]],
                ),
            ],
            'berkas' => [
                'title' => 'Daftar Berkas', 'headers' => ['Jenis', 'ID Terkait', 'Referensi', 'Lokasi Dalam ZIP', 'Ketersediaan'],
                'rows' => fn () => collect($this->media($filters))->map(fn ($m) => [$m['type'], $m['id'], $m['reference'], $m['archive'], $m['source'] ? 'Tersedia' : 'Tidak ditemukan / referensi tidak valid']),
            ],
        ];
    }

    public function media(array $filters): array
    {
        $media = [];
        foreach (Product::whereNotNull('image')->select('id', 'image')->lazyById(250) as $product) {
            $media[] = $this->mediaEntry('produk', $product->id, $product->image, 'public', 'products/');
        }
        foreach ($this->orders($filters)->whereNotNull('payment_proof')->select('id', 'payment_proof')->lazyById(250) as $order) {
            $media[] = $this->mediaEntry('bukti_bayar', $order->id, $order->payment_proof, 'local', 'payment_proofs/');
        }
        if ($qris = ShopSetting::getValue('qris_image')) {
            $media[] = $this->mediaEntry('qris', null, $qris, 'public', 'qris/');
        }
        $reference = config('floor_plan.background.path');
        $media[] = $this->mediaEntry('latar_denah', null, $reference, null, 'images/floor-plans/');

        return $media;
    }

    private function mediaEntry(string $type, ?int $id, ?string $reference, ?string $disk, string $prefix): array
    {
        $source = null;
        $archive = null;
        if (is_string($reference) && str_starts_with($reference, $prefix)
            && ! preg_match('~[\\\\\x00]|(?:^|/)\.\.(?:/|$)~', $reference)
            && preg_match('/\.(png|jpe?g|webp)$/i', $reference)) {
            $candidate = $disk ? Storage::disk($disk)->path($reference) : public_path($reference);
            $root = realpath($disk ? Storage::disk($disk)->path($prefix) : public_path($prefix));
            $resolved = realpath($candidate);
            if ($root && $resolved && str_starts_with($resolved, $root.DIRECTORY_SEPARATOR) && is_file($resolved)) {
                $source = $resolved;
                $archive = 'berkas/'.$type.'/'.($id ?? 'toko').'-'.basename($reference);
            }
        }

        return compact('type', 'id', 'reference', 'archive', 'source');
    }
}
