@extends('layouts.karyawan')

@section('title', 'Ketersediaan Meja')
@section('page-title', 'Ketersediaan Meja')
@section('page-description', 'Pantau denah area utama dan perbarui status meja secara manual')

@section('page-actions')
    <button id='refresh-layout' type='button' class='table-refresh-button' aria-label='Muat ulang denah meja'>
        <svg class='w-4 h-4' fill='none' stroke='currentColor' viewBox='0 0 24 24' stroke-width='1.8' aria-hidden='true'><path stroke-linecap='round' stroke-linejoin='round' d='M16.023 9.348h4.992V4.356m-1.636 14.488a9 9 0 01-12.728.7M3.985 14.652H-1.007v4.992m1.636-14.488a9 9 0 0112.728-.7'/></svg>
        <span>Muat ulang</span>
    </button>
@endsection

@push('styles')
<style>
    .table-workspace { --available:#16856f; --available-soft:#e8f7f1; --occupied:#d9634d; --occupied-soft:#fff0ed; --reserved:#bd7a24; --reserved-soft:#fff6e5; --unavailable:#7a818c; --unavailable-soft:#f1f3f5; }
    .table-refresh-button { display:flex; align-items:center; gap:.5rem; border:1px solid rgba(161,136,127,.45); border-radius:.8rem; background:#fff; color:#5d4037; font-size:.75rem; font-weight:700; padding:.62rem .82rem; transition:all .2s ease; }
    .table-refresh-button:hover { border-color:#a1887f; background:#faf7f4; transform:translateY(-1px); } .table-refresh-button:disabled { cursor:wait; opacity:.6; transform:none; } .table-refresh-button.is-loading svg { animation:table-spin .85s linear infinite; }
    @keyframes table-spin { to { transform:rotate(360deg); } }
    .table-hero { position:relative; overflow:hidden; border:1px solid rgba(215,204,200,.72); border-radius:1.5rem; background:linear-gradient(135deg,#fff 0%,#f8f1e8 100%); padding:1.25rem; } .table-hero::after { content:''; position:absolute; right:-4rem; top:-4rem; width:14rem; height:14rem; border-radius:999px; background:rgba(161,136,127,.1); pointer-events:none; } .table-hero-copy { position:relative; z-index:1; }
    .table-last-updated { display:inline-flex; align-items:center; gap:.4rem; margin-top:.75rem; border-radius:999px; background:rgba(255,255,255,.88); border:1px solid rgba(215,204,200,.65); color:#8d6e63; font-size:.7rem; font-weight:600; padding:.4rem .65rem; }
    .table-stat-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.75rem; margin-top:1rem; } .table-stat-card { min-width:0; border:1px solid rgba(215,204,200,.62); border-radius:1rem; background:#fff; box-shadow:0 3px 12px rgba(62,39,35,.035); padding:.9rem; } .table-stat-card p { margin:0; } .table-stat-label { color:#8d6e63; font-size:.66rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase; } .table-stat-value { margin-top:.35rem !important; color:#3e2723; font-size:1.45rem; font-weight:800; line-height:1; } .table-stat-detail { margin-top:.28rem !important; color:#a1887f; font-size:.68rem; } .table-stat-accent { display:inline-flex; width:.5rem; height:.5rem; margin-right:.35rem; border-radius:999px; }
    .accent-available { background:var(--available); } .accent-occupied { background:var(--occupied); } .accent-reserved { background:var(--reserved); } .accent-unavailable { background:var(--unavailable); }
    .table-board { margin-top:1rem; border:1px solid rgba(215,204,200,.7); border-radius:1.25rem; background:#fff; box-shadow:0 8px 28px rgba(62,39,35,.055); overflow:hidden; } .table-board-header { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:.8rem; padding:1rem 1.1rem; border-bottom:1px solid rgba(215,204,200,.55); } .table-board-title { color:#3e2723; font-size:.9rem; font-weight:800; } .table-board-subtitle { margin-top:.15rem; color:#a1887f; font-size:.7rem; } .table-legend { display:flex; flex-wrap:wrap; gap:.45rem .75rem; color:#6d5b55; font-size:.67rem; font-weight:650; } .table-legend-item { display:inline-flex; align-items:center; gap:.32rem; white-space:nowrap; } .table-legend-dot { width:.55rem; height:.55rem; border-radius:999px; }
    .table-floor-scroll { overflow-x:auto; padding:.8rem; background:#fbfaf8; } .table-floor-canvas { position:relative; min-width:640px; aspect-ratio:3 / 2; overflow:hidden; border:1px solid #e1d6cf; border-radius:1rem; background-color:#f8f1e8; box-shadow:inset 0 0 0 5px rgba(255,255,255,.5); } .table-floor-canvas.has-grid { background-image:linear-gradient(rgba(141,110,99,.09) 1px,transparent 1px),linear-gradient(90deg,rgba(141,110,99,.09) 1px,transparent 1px); background-size:5% 7.5%; } .table-floor-canvas.is-loading::after { content:'Memuat denah...'; display:flex; align-items:center; justify-content:center; position:absolute; inset:0; z-index:8; background:rgba(250,250,240,.88); color:#8d6e63; font-size:.78rem; font-weight:700; } .table-floor-empty { min-width:640px; aspect-ratio:3 / 2; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:.5rem; color:#8d6e63; text-align:center; }
    .floor-marker { position:absolute; z-index:1; display:flex; align-items:center; justify-content:center; transform:translate(-50%,-50%); border:1px dashed rgba(141,110,99,.55); border-radius:.65rem; background:rgba(255,255,255,.74); color:#705a52; font-size:.58rem; font-weight:750; letter-spacing:.03em; text-align:center; text-transform:uppercase; pointer-events:none; } .marker--counter { min-width:8.6rem; padding:.45rem .8rem; color:#fff; border-style:solid; border-color:#5d4037; background:#5d4037; box-shadow:0 5px 12px rgba(62,39,35,.18); } .marker--window { min-width:1.2rem; min-height:7rem; writing-mode:vertical-rl; padding:.45rem .2rem; color:#52728c; border-color:#8bb9d3; background:rgba(223,243,251,.82); } .marker--entrance { min-width:7rem; padding:.35rem .6rem; color:#72552e; border-color:#d3b27c; background:#fff7e9; }
    .floor-table { --table-color:#7a818c; position:absolute; z-index:3; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:.18rem; border:2px solid var(--table-color); background:#fff; color:#3e2723; box-shadow:0 7px 16px rgba(62,39,35,.12); cursor:pointer; transform:rotate(var(--table-rotation,0deg)); transition:transform .2s ease,box-shadow .2s ease,background .2s ease; } .floor-table:hover,.floor-table:focus-visible { z-index:5; outline:none; transform:rotate(var(--table-rotation,0deg)) translateY(-3px) scale(1.035); box-shadow:0 11px 22px rgba(62,39,35,.18); } .floor-table:focus-visible { box-shadow:0 0 0 4px rgba(62,39,35,.18),0 11px 22px rgba(62,39,35,.18); } .floor-table::before { content:''; width:.48rem; height:.48rem; border-radius:999px; background:var(--table-color); box-shadow:0 0 0 4px color-mix(in srgb,var(--table-color) 15%,transparent); } .floor-table.shape-round { border-radius:999px; } .floor-table.shape-square { border-radius:.8rem; } .floor-table.shape-rectangle { border-radius:.75rem; }
    .floor-table.status-available { --table-color:var(--available); background:var(--available-soft); } .floor-table.status-occupied { --table-color:var(--occupied); background:var(--occupied-soft); } .floor-table.status-reserved { --table-color:var(--reserved); background:var(--reserved-soft); } .floor-table.status-unavailable { --table-color:var(--unavailable); background:var(--unavailable-soft); opacity:.82; } .floor-table-code { color:#3e2723; font-size:clamp(.58rem,1.2vw,.8rem); font-weight:850; letter-spacing:.04em; line-height:1; } .floor-table-capacity { color:#6d5b55; font-size:clamp(.48rem,1vw,.63rem); font-weight:650; line-height:1; }
    .table-board-footer { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:.85rem 1.1rem; border-top:1px solid rgba(215,204,200,.55); color:#8d6e63; font-size:.7rem; } .table-board-footer strong { color:#5d4037; }
    .table-toast { position:fixed; z-index:80; right:1rem; bottom:1rem; max-width:min(24rem,calc(100vw - 2rem)); border:1px solid; border-radius:1rem; box-shadow:0 14px 34px rgba(62,39,35,.2); padding:.85rem 1rem; font-size:.78rem; font-weight:650; transform:translateY(1rem); opacity:0; pointer-events:none; transition:all .24s ease; } .table-toast.is-visible { transform:translateY(0); opacity:1; } .table-toast.success { color:#0f6a58; border-color:#bde8db; background:#effbf6; } .table-toast.error { color:#a14232; border-color:#ffd1c8; background:#fff6f4; } .table-toast.info { color:#72552e; border-color:#efd6a5; background:#fffaf0; }
    .table-modal-backdrop { position:fixed; inset:0; z-index:60; display:flex; align-items:end; justify-content:center; padding:1rem; background:rgba(27,15,11,.45); backdrop-filter:blur(4px); } .table-modal-backdrop.hidden { display:none; } .table-modal { width:100%; max-width:31rem; max-height:calc(100vh - 2rem); overflow:auto; border:1px solid rgba(255,255,255,.45); border-radius:1.4rem; background:#fff; box-shadow:0 24px 64px rgba(27,15,11,.3); animation:table-modal-in .22s ease-out; } @keyframes table-modal-in { from { transform:translateY(1rem); opacity:0; } to { transform:translateY(0); opacity:1; } } .table-modal-header { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; padding:1.25rem 1.25rem 1rem; border-bottom:1px solid rgba(215,204,200,.58); } .table-modal-title { color:#3e2723; font-size:1.05rem; font-weight:850; } .table-modal-description { margin-top:.2rem; color:#8d6e63; font-size:.72rem; } .table-close { display:inline-flex; align-items:center; justify-content:center; width:2rem; height:2rem; border-radius:.7rem; color:#8d6e63; transition:background .18s ease; } .table-close:hover { background:#f4efec; color:#3e2723; }
    .table-modal-body { padding:1.1rem 1.25rem 1.25rem; } .table-current-status { display:flex; align-items:center; justify-content:space-between; gap:.75rem; margin-bottom:1rem; border:1px solid rgba(215,204,200,.55); border-radius:.9rem; background:#fbfaf8; padding:.75rem .85rem; } .table-current-status p { margin:0; } .table-current-caption { color:#a1887f; font-size:.62rem; font-weight:750; letter-spacing:.08em; text-transform:uppercase; } .table-current-by { margin-top:.17rem !important; color:#6d5b55; font-size:.7rem; } .table-status-chip { display:inline-flex; align-items:center; gap:.34rem; border-radius:999px; padding:.36rem .56rem; font-size:.64rem; font-weight:800; white-space:nowrap; } .table-status-chip::before { content:''; width:.42rem; height:.42rem; border-radius:999px; background:currentColor; } .status-chip--available { color:var(--available); background:var(--available-soft); } .status-chip--occupied { color:var(--occupied); background:var(--occupied-soft); } .status-chip--reserved { color:var(--reserved); background:var(--reserved-soft); } .status-chip--unavailable { color:var(--unavailable); background:var(--unavailable-soft); }
    .status-choice-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.55rem; margin-top:.45rem; } .status-choice { display:flex; align-items:center; gap:.55rem; min-height:3.1rem; border:1px solid rgba(215,204,200,.72); border-radius:.85rem; background:#fff; color:#6d5b55; padding:.62rem .72rem; text-align:left; font-size:.72rem; font-weight:750; transition:all .18s ease; } .status-choice:hover { border-color:#bca8a0; background:#fbfaf8; } .status-choice[aria-pressed='true'] { border-color:var(--choice-color); background:var(--choice-soft); color:var(--choice-color); box-shadow:0 0 0 3px color-mix(in srgb,var(--choice-color) 10%,transparent); } .status-choice-dot { width:.55rem; height:.55rem; border-radius:999px; background:var(--choice-color); flex:none; } .choice-available { --choice-color:var(--available); --choice-soft:var(--available-soft); } .choice-occupied { --choice-color:var(--occupied); --choice-soft:var(--occupied-soft); } .choice-reserved { --choice-color:var(--reserved); --choice-soft:var(--reserved-soft); } .choice-unavailable { --choice-color:var(--unavailable); --choice-soft:var(--unavailable-soft); }
    .table-note-label { display:block; margin:.95rem 0 .38rem; color:#6d5b55; font-size:.72rem; font-weight:800; } .table-note-label span { color:#a1887f; font-weight:500; } .table-note { width:100%; min-height:5.5rem; resize:vertical; border:1px solid rgba(215,204,200,.78); border-radius:.85rem; color:#3e2723; font-size:.75rem; line-height:1.55; padding:.7rem .75rem; outline:none; transition:border .18s ease,box-shadow .18s ease; } .table-note:focus { border-color:#a1887f; box-shadow:0 0 0 3px rgba(161,136,127,.14); } .table-modal-actions { display:flex; gap:.6rem; margin-top:1rem; } .table-button { flex:1; display:inline-flex; align-items:center; justify-content:center; gap:.45rem; border-radius:.8rem; padding:.75rem .9rem; font-size:.75rem; font-weight:800; transition:all .18s ease; } .table-button-primary { color:#f5f5dc; background:#3e2723; } .table-button-primary:hover { background:#5d4037; } .table-button-primary:disabled { cursor:wait; opacity:.65; } .table-button-secondary { border:1px solid rgba(215,204,200,.8); color:#6d5b55; background:#fff; } .table-button-secondary:hover { border-color:#a1887f; color:#3e2723; background:#faf7f4; }
    @media (max-width:639px) { .table-hero { border-radius:1.05rem; padding:1rem; } .table-stat-grid { gap:.6rem; } .table-stat-card { padding:.78rem; } .table-stat-card:last-child { grid-column:span 2; } .table-board { border-radius:1rem; } .table-board-header { align-items:flex-start; padding:.9rem; } .table-legend { gap:.4rem .6rem; } .table-floor-scroll { overflow-x:hidden; padding:.5rem; } .table-floor-canvas,.table-floor-empty { width:100%; min-width:0; } .table-board-footer { align-items:flex-start; flex-direction:column; gap:.35rem; padding:.8rem .9rem; } .table-modal-backdrop { padding:.5rem; } .table-modal { max-height:calc(100dvh - 1rem); border-radius:1.1rem; } .table-modal-header { padding:.95rem .95rem .8rem; } .table-modal-body { padding:.9rem .95rem .95rem; } }
    @media (max-width:374px) { .status-choice-grid { grid-template-columns:1fr; } .table-current-status { align-items:flex-start; flex-direction:column; } }
    @media (min-width:640px) and (max-width:767px) { .table-stat-grid { grid-template-columns:repeat(3,minmax(0,1fr)); } .table-hero { padding:1.5rem; } .table-floor-scroll { padding:1rem; } .table-floor-canvas,.table-floor-empty { width:100%; min-width:0; } .table-modal-backdrop { align-items:center; } }
    @media (min-width:768px) { .table-stat-grid { grid-template-columns:repeat(5,minmax(0,1fr)); } .table-hero { padding:1.5rem; } .table-floor-scroll { padding:1rem; } .table-floor-canvas,.table-floor-empty { width:100%; min-width:0; } .table-modal-backdrop { align-items:center; } }
</style>
@endpush

@section('content')
<div class='table-workspace'>
    <section class='table-hero'>
        <div class='table-hero-copy'>
            <p class='text-[0.68rem] font-extrabold uppercase tracking-[0.16em] text-caramel'>Operasional Kedai</p>
            <h3 id='layout-name' class='mt-1 font-serif text-2xl font-bold text-espresso'>Ketersediaan meja</h3>
            <p id='layout-description' class='mt-2 max-w-2xl text-sm leading-6 text-caramel-dark'>Memuat denah area dan status meja terbaru.</p>
            <p id='layout-last-updated' class='table-last-updated'>Menunggu data denah...</p>
        </div>
        <div class='table-stat-grid' aria-live='polite'>
            <div class='table-stat-card'><p class='table-stat-label'>Total</p><p id='stat-total' class='table-stat-value'>–</p><p class='table-stat-detail'>Meja aktif</p></div>
            <div class='table-stat-card'><p class='table-stat-label'><span class='table-stat-accent accent-available'></span>Tersedia</p><p id='stat-available' class='table-stat-value'>–</p><p class='table-stat-detail'>Siap digunakan</p></div>
            <div class='table-stat-card'><p class='table-stat-label'><span class='table-stat-accent accent-occupied'></span>Terisi</p><p id='stat-occupied' class='table-stat-value'>–</p><p class='table-stat-detail'>Sedang dipakai</p></div>
            <div class='table-stat-card'><p class='table-stat-label'><span class='table-stat-accent accent-reserved'></span>Dipesan</p><p id='stat-reserved' class='table-stat-value'>–</p><p class='table-stat-detail'>Disiapkan manual</p></div>
            <div class='table-stat-card'><p class='table-stat-label'><span class='table-stat-accent accent-unavailable'></span>Nonaktif</p><p id='stat-unavailable' class='table-stat-value'>–</p><p class='table-stat-detail'>Tidak tersedia</p></div>
        </div>
    </section>

    <section class='table-board' aria-labelledby='floor-board-title'>
        <div class='table-board-header'>
            <div>
                <h4 id='floor-board-title' class='table-board-title'>Denah area</h4>
                <p class='table-board-subtitle'>Pilih meja untuk melihat detail dan memperbarui statusnya.</p>
            </div>
            <div class='table-legend' aria-label='Legenda status meja'>
                <span class='table-legend-item'><span class='table-legend-dot accent-available'></span>Tersedia</span>
                <span class='table-legend-item'><span class='table-legend-dot accent-occupied'></span>Terisi</span>
                <span class='table-legend-item'><span class='table-legend-dot accent-reserved'></span>Dipesan</span>
                <span class='table-legend-item'><span class='table-legend-dot accent-unavailable'></span>Tidak tersedia</span>
            </div>
        </div>
        <div class='table-floor-scroll'>
            <div id='floor-canvas' class='table-floor-canvas is-loading' role='region' aria-label='Denah meja interaktif'></div>
            <div id='floor-empty' class='table-floor-empty hidden' role='status'>
                <svg class='h-8 w-8 text-caramel' fill='none' stroke='currentColor' viewBox='0 0 24 24' stroke-width='1.5' aria-hidden='true'><path stroke-linecap='round' stroke-linejoin='round' d='M3 21h18M5.25 21V7.5A2.25 2.25 0 017.5 5.25h9A2.25 2.25 0 0118.75 7.5V21M9 9.75h.008v.008H9V9.75zm3 0h.008v.008H12V9.75zm3 0h.008v.008H15V9.75z'/></svg>
                <p class='font-semibold'>Denah meja belum tersedia</p>
                <p class='text-xs'>Silakan muat ulang beberapa saat lagi.</p>
            </div>
        </div>
        <div class='table-board-footer'>
            <span>Data diperbarui otomatis setiap <strong>15 detik</strong> saat halaman aktif.</span>
            <span id='board-table-count'>– meja</span>
        </div>
    </section>
</div>

<div id='table-toast' class='table-toast' role='status' aria-live='polite'></div>
@endsection

@push('modals')
<div id='table-modal-backdrop' class='table-modal-backdrop hidden' aria-hidden='true'>
    <div id='table-modal' class='table-modal' role='dialog' aria-modal='true' aria-labelledby='table-modal-title'>
        <div class='table-modal-header'>
            <div>
                <h3 id='table-modal-title' class='table-modal-title'>Detail meja</h3>
                <p id='table-modal-description' class='table-modal-description'></p>
            </div>
            <button id='table-modal-close' type='button' class='table-close' aria-label='Tutup detail meja'>
                <svg class='w-5 h-5' fill='none' stroke='currentColor' viewBox='0 0 24 24' stroke-width='1.8' aria-hidden='true'><path stroke-linecap='round' stroke-linejoin='round' d='M6 18L18 6M6 6l12 12'/></svg>
            </button>
        </div>
        <div class='table-modal-body'>
            <div class='table-current-status'>
                <div>
                    <p class='table-current-caption'>Pembaruan terakhir</p>
                    <p id='table-current-by' class='table-current-by'>Belum ada pembaruan status.</p>
                </div>
                <span id='table-current-status' class='table-status-chip status-chip--available'>Tersedia</span>
            </div>
            <p class='text-xs font-extrabold uppercase tracking-[0.1em] text-caramel'>Pilih status baru</p>
            <div class='status-choice-grid'>
                <button type='button' class='status-choice choice-available' data-status-choice='available' aria-pressed='false'><span class='status-choice-dot'></span>Tersedia</button>
                <button type='button' class='status-choice choice-occupied' data-status-choice='occupied' aria-pressed='false'><span class='status-choice-dot'></span>Terisi</button>
                <button type='button' class='status-choice choice-reserved' data-status-choice='reserved' aria-pressed='false'><span class='status-choice-dot'></span>Dipesan</button>
                <button type='button' class='status-choice choice-unavailable' data-status-choice='unavailable' aria-pressed='false'><span class='status-choice-dot'></span>Tidak tersedia</button>
            </div>
            <label for='table-status-note' class='table-note-label'>Catatan operasional <span>opsional, maksimal 500 karakter</span></label>
            <textarea id='table-status-note' class='table-note' maxlength='500' placeholder='Contoh: menunggu dibersihkan setelah pelanggan pulang'></textarea>
            <div class='table-modal-actions'>
                <button id='table-modal-cancel' type='button' class='table-button table-button-secondary'>Batal</button>
                <button id='table-modal-save' type='button' class='table-button table-button-primary'>Simpan status</button>
            </div>
        </div>
    </div>
</div>
@endpush

@push('scripts')
<script>
(() => {
    const endpoints = {
        data: @json(route('karyawan.tables.data')),
        update: @json(route('karyawan.tables.status.update', ['coffeeTable' => '__TABLE_ID__'])),
    };
    const csrfToken = document.querySelector('meta[name=csrf-token]').getAttribute('content');
    const statusLabels = { available: 'Tersedia', occupied: 'Terisi', reserved: 'Dipesan', unavailable: 'Tidak tersedia' };
    const state = { layout: null, selectedTableId: null, selectedStatus: null, loading: false, saving: false, toastTimer: null, pollTimer: null };
    const elements = {
        refresh: document.getElementById('refresh-layout'),
        canvas: document.getElementById('floor-canvas'),
        empty: document.getElementById('floor-empty'),
        toast: document.getElementById('table-toast'),
        modalBackdrop: document.getElementById('table-modal-backdrop'),
        modal: document.getElementById('table-modal'),
        modalTitle: document.getElementById('table-modal-title'),
        modalDescription: document.getElementById('table-modal-description'),
        currentStatus: document.getElementById('table-current-status'),
        currentBy: document.getElementById('table-current-by'),
        note: document.getElementById('table-status-note'),
        save: document.getElementById('table-modal-save'),
    };

    const findTable = (id) => state.layout?.tables.find((table) => Number(table.id) === Number(id)) ?? null;
    const isModalOpen = () => !elements.modalBackdrop.classList.contains('hidden');
    const statusLabel = (status) => statusLabels[status] ?? 'Tidak diketahui';

    function formatDate(value) {
        if (!value) return 'Belum ada pembaruan status.';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return 'Waktu pembaruan tidak tersedia.';
        return new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(date);
    }

    function setLoading(isLoading) {
        state.loading = isLoading;
        elements.refresh.disabled = isLoading;
        elements.refresh.classList.toggle('is-loading', isLoading);
        elements.canvas.classList.toggle('is-loading', isLoading && !state.layout);
    }

    function setText(id, value) {
        document.getElementById(id).textContent = value;
    }

    function renderSummary(summary) {
        setText('stat-total', summary.total ?? 0);
        setText('stat-available', summary.available ?? 0);
        setText('stat-occupied', summary.occupied ?? 0);
        setText('stat-reserved', summary.reserved ?? 0);
        setText('stat-unavailable', summary.unavailable ?? 0);
        setText('board-table-count', (summary.total ?? 0) + ' meja aktif');
    }

    function renderMarker(marker) {
        const types = ['counter', 'window', 'entrance'];
        const markerElement = document.createElement('div');
        const type = types.includes(marker.type) ? marker.type : 'marker';
        markerElement.className = 'floor-marker marker--' + type;
        markerElement.style.left = Number(marker.position_x ?? 50) + '%';
        markerElement.style.top = Number(marker.position_y ?? 50) + '%';
        markerElement.textContent = marker.label ?? 'Area';
        elements.canvas.appendChild(markerElement);
    }

    function renderTable(table) {
        const shapes = ['round', 'square', 'rectangle'];
        const statuses = ['available', 'occupied', 'reserved', 'unavailable'];
        const shape = shapes.includes(table.shape) ? table.shape : 'square';
        const status = statuses.includes(table.status) ? table.status : 'unavailable';
        const tableElement = document.createElement('button');
        const code = document.createElement('span');
        const capacity = document.createElement('span');
        tableElement.type = 'button';
        tableElement.className = 'floor-table shape-' + shape + ' status-' + status;
        tableElement.style.left = Number(table.position_x) + '%';
        tableElement.style.top = Number(table.position_y) + '%';
        tableElement.style.width = Number(table.width) + '%';
        tableElement.style.height = Number(table.height) + '%';
        tableElement.style.setProperty('--table-rotation', Number(table.rotation ?? 0) + 'deg');
        tableElement.setAttribute('aria-label', table.code + ', ' + table.capacity + ' kursi, status ' + statusLabel(status) + '. Tekan untuk memperbarui status.');
        tableElement.title = table.name + ' · ' + statusLabel(status);
        code.className = 'floor-table-code';
        code.textContent = table.code;
        capacity.className = 'floor-table-capacity';
        capacity.textContent = table.capacity + ' kursi';
        tableElement.append(code, capacity);
        tableElement.addEventListener('click', () => openModal(table.id));
        elements.canvas.appendChild(tableElement);
    }

    function renderCanvas() {
        elements.canvas.replaceChildren();
        const config = state.layout.layout.background_config ?? {};
        elements.canvas.classList.toggle('has-grid', config.show_grid !== false);
        elements.canvas.style.aspectRatio = String(state.layout.layout.canvas_width ?? 1200) + ' / ' + String(state.layout.layout.canvas_height ?? 800);
        (Array.isArray(config.elements) ? config.elements : []).forEach(renderMarker);
        state.layout.tables.forEach(renderTable);
    }

    function renderLayout() {
        if (!state.layout) return;
        const layout = state.layout.layout;
        setText('layout-name', layout.name ?? 'Ketersediaan meja');
        setText('layout-description', layout.description ?? 'Status meja area utama Ciks Coffee.');
        setText('layout-last-updated', 'Denah diperbarui ' + formatDate(layout.updated_at));
        renderSummary(state.layout.summary);
        renderCanvas();
        elements.empty.classList.add('hidden');
        elements.canvas.classList.remove('hidden');
    }

    function showEmpty(message) {
        elements.canvas.replaceChildren();
        elements.canvas.classList.add('hidden');
        elements.empty.classList.remove('hidden');
        elements.empty.querySelector('p:nth-of-type(2)').textContent = message;
    }

    function showToast(message, type = 'info') {
        window.clearTimeout(state.toastTimer);
        elements.toast.textContent = message;
        elements.toast.className = 'table-toast ' + type + ' is-visible';
        state.toastTimer = window.setTimeout(() => elements.toast.classList.remove('is-visible'), 4500);
    }

    async function loadLayout({ quiet = false } = {}) {
        if (state.loading) return;
        setLoading(true);
        try {
            const response = await fetch(endpoints.data, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            const payload = await response.json().catch(() => null);
            if (!response.ok || !payload?.success) throw new Error(payload?.message ?? 'Denah meja tidak dapat dimuat.');
            state.layout = payload.data;
            renderLayout();
            if (!quiet) showToast('Denah meja sudah diperbarui.', 'success');
        } catch (error) {
            if (!state.layout) showEmpty(error.message ?? 'Gagal memuat denah meja.');
            if (!quiet) showToast(error.message ?? 'Gagal memuat denah meja.', 'error');
        } finally {
            setLoading(false);
        }
    }
    function renderStatusChoices() {
        document.querySelectorAll('[data-status-choice]').forEach((button) => {
            const active = button.dataset.statusChoice === state.selectedStatus;
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
    }

    function openModal(tableId, force = false) {
        const table = findTable(tableId);
        if (!table || (state.saving && !force)) return;
        state.selectedTableId = table.id;
        state.selectedStatus = table.status;
        elements.modalTitle.textContent = table.code + ' · ' + table.name;
        const shapeLabel = table.shape === 'round' ? 'meja bulat' : table.shape === 'rectangle' ? 'meja persegi panjang' : 'meja persegi';
        elements.modalDescription.textContent = table.capacity + ' kursi · ' + shapeLabel;
        elements.currentStatus.className = 'table-status-chip status-chip--' + table.status;
        elements.currentStatus.textContent = statusLabel(table.status);
        const actor = table.status_updated_by?.name ? ' oleh ' + table.status_updated_by.name : '';
        elements.currentBy.textContent = table.status_updated_at ? formatDate(table.status_updated_at) + actor : 'Belum ada pembaruan status.';
        elements.note.value = table.status_note ?? '';
        elements.save.textContent = 'Simpan status';
        renderStatusChoices();
        elements.modalBackdrop.classList.remove('hidden');
        elements.modalBackdrop.setAttribute('aria-hidden', 'false');
        window.setTimeout(() => elements.note.focus(), 80);
    }

    function closeModal(force = false) {
        if (state.saving && !force) return;
        elements.modalBackdrop.classList.add('hidden');
        elements.modalBackdrop.setAttribute('aria-hidden', 'true');
        state.selectedTableId = null;
        state.selectedStatus = null;
    }

    function replaceTable(updatedTable) {
        if (!state.layout || !updatedTable) return;
        const index = state.layout.tables.findIndex((table) => Number(table.id) === Number(updatedTable.id));
        if (index >= 0) state.layout.tables.splice(index, 1, updatedTable);
        const summary = { total: state.layout.tables.length, available: 0, occupied: 0, reserved: 0, unavailable: 0 };
        state.layout.tables.forEach((table) => { if (Object.prototype.hasOwnProperty.call(summary, table.status)) summary[table.status] += 1; });
        state.layout.summary = summary;
        renderLayout();
    }

    async function saveStatus() {
        const table = findTable(state.selectedTableId);
        if (!table || !state.selectedStatus || state.saving) return;
        state.saving = true;
        elements.save.disabled = true;
        elements.save.textContent = 'Menyimpan...';
        try {
            const url = endpoints.update.replace('__TABLE_ID__', String(table.id));
            const response = await fetch(url, {
                method: 'PATCH',
                headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ status: state.selectedStatus, note: elements.note.value.trim(), version: table.version }),
            });
            const payload = await response.json().catch(() => null);
            if (response.status === 409 && payload?.data) {
                replaceTable(payload.data);
                openModal(payload.data.id, true);
                showToast(payload.message ?? 'Data meja telah berubah. Periksa data terbaru lalu simpan lagi.', 'info');
                return;
            }
            if (!response.ok || !payload?.success) throw new Error(payload?.message ?? 'Status meja tidak dapat diperbarui.');
            replaceTable(payload.data);
            closeModal(true);
            showToast(payload.message ?? 'Status meja berhasil diperbarui.', 'success');
        } catch (error) {
            showToast(error.message ?? 'Status meja tidak dapat diperbarui.', 'error');
        } finally {
            state.saving = false;
            elements.save.disabled = false;
            if (isModalOpen()) elements.save.textContent = 'Simpan status';
        }
    }

    elements.refresh.addEventListener('click', () => loadLayout());
    document.querySelectorAll('[data-status-choice]').forEach((button) => button.addEventListener('click', () => {
        if (state.saving) return;
        state.selectedStatus = button.dataset.statusChoice;
        renderStatusChoices();
    }));
    document.getElementById('table-modal-close').addEventListener('click', closeModal);
    document.getElementById('table-modal-cancel').addEventListener('click', closeModal);
    elements.modalBackdrop.addEventListener('click', (event) => { if (event.target === elements.modalBackdrop) closeModal(); });
    elements.save.addEventListener('click', saveStatus);
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape') closeModal(); });
    document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'visible' && !isModalOpen()) loadLayout({ quiet: true }); });
    state.pollTimer = window.setInterval(() => { if (document.visibilityState === 'visible' && !isModalOpen() && !state.saving) loadLayout({ quiet: true }); }, 15000);
    window.addEventListener('beforeunload', () => window.clearInterval(state.pollTimer));
    loadLayout({ quiet: true });
})();
</script>
@endpush
