@extends('layouts.pemilik')

@section('title', 'Manajemen Meja')
@section('page-title', 'Manajemen Meja')
@section('page-description', 'Pantau ketersediaan dan atur denah meja Ciks Coffee')

@section('page-actions')
    <button id='owner-refresh' type='button' class='owner-action owner-action-light'>Muat ulang</button>
    <button id='owner-add-table' type='button' class='owner-action owner-action-light'>Tambah meja</button>
    <button id='owner-edit-toggle' type='button' class='owner-action owner-action-dark'>Atur layout</button>
@endsection

@push('styles')
<style>
    .owner-workspace { --available:#16856f; --available-soft:#e8f7f1; --occupied:#d9634d; --occupied-soft:#fff0ed; --reserved:#bd7a24; --reserved-soft:#fff6e5; --unavailable:#7a818c; --unavailable-soft:#f1f3f5; }
    .owner-action { display:inline-flex; align-items:center; justify-content:center; border-radius:.8rem; padding:.62rem .8rem; font-size:.72rem; font-weight:800; transition:all .18s ease; } .owner-action:disabled { cursor:wait; opacity:.6; } .owner-action-light { border:1px solid rgba(161,136,127,.45); background:#fff; color:#5d4037; } .owner-action-light:hover { border-color:#a1887f; background:#faf7f4; } .owner-action-dark { background:#3e2723; color:#f5f5dc; } .owner-action-dark:hover { background:#5d4037; } .owner-action-warn { background:#b4533e; color:#fff; }
    .owner-intro { display:flex; flex-wrap:wrap; align-items:flex-start; justify-content:space-between; gap:1rem; overflow:hidden; position:relative; border:1px solid rgba(215,204,200,.7); border-radius:1.4rem; background:linear-gradient(135deg,#fff,#f8f1e8); padding:1.25rem; } .owner-intro::after { content:''; position:absolute; width:13rem; height:13rem; right:-5rem; top:-5rem; border-radius:999px; background:rgba(161,136,127,.1); } .owner-intro > * { position:relative; z-index:1; } .owner-updated { display:inline-flex; margin-top:.5rem; border:1px solid rgba(215,204,200,.7); border-radius:999px; background:rgba(255,255,255,.88); color:#8d6e63; padding:.34rem .6rem; font-size:.67rem; font-weight:700; }
    .owner-mode-tabs { display:flex; gap:.45rem; border:1px solid rgba(215,204,200,.7); border-radius:.9rem; background:rgba(255,255,255,.7); padding:.25rem; } .owner-mode-tab { border-radius:.65rem; color:#8d6e63; font-size:.7rem; font-weight:800; padding:.5rem .7rem; } .owner-mode-tab.is-active { background:#3e2723; color:#f5f5dc; box-shadow:0 3px 8px rgba(62,39,35,.14); }
    .owner-stat-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.7rem; margin-top:1rem; } .owner-stat { border:1px solid rgba(215,204,200,.6); border-radius:1rem; background:#fff; box-shadow:0 3px 12px rgba(62,39,35,.04); padding:.85rem; } .owner-stat p { margin:0; } .owner-stat-label { color:#8d6e63; font-size:.62rem; font-weight:750; letter-spacing:.08em; text-transform:uppercase; } .owner-stat-value { margin-top:.3rem !important; color:#3e2723; font-size:1.35rem; font-weight:850; line-height:1; } .owner-dot { display:inline-block; width:.5rem; height:.5rem; margin-right:.28rem; border-radius:999px; } .dot-available { background:var(--available); } .dot-occupied { background:var(--occupied); } .dot-reserved { background:var(--reserved); } .dot-unavailable { background:var(--unavailable); }
    .owner-layout-grid { display:grid; gap:1rem; margin-top:1rem; } .owner-card { border:1px solid rgba(215,204,200,.7); border-radius:1.2rem; background:#fff; box-shadow:0 7px 24px rgba(62,39,35,.05); } .owner-card-header { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:.75rem; border-bottom:1px solid rgba(215,204,200,.55); padding:.95rem 1rem; } .owner-card-title { color:#3e2723; font-size:.84rem; font-weight:850; } .owner-card-subtitle { margin-top:.12rem; color:#a1887f; font-size:.68rem; } .owner-canvas-scroll { overflow:auto; padding:.8rem; background:#fbfaf8; } .owner-preview-mobile .owner-canvas { width:390px; min-width:390px; margin:auto; } .owner-canvas { position:relative; min-width:680px; aspect-ratio:3 / 2; overflow:hidden; border:1px solid #e1d6cf; border-radius:1rem; background:#f8f1e8; box-shadow:inset 0 0 0 5px rgba(255,255,255,.48); touch-action:none; } .owner-canvas.has-grid { background-image:linear-gradient(rgba(141,110,99,.09) 1px,transparent 1px),linear-gradient(90deg,rgba(141,110,99,.09) 1px,transparent 1px); background-size:5% 7.5%; } .owner-canvas.is-editing { box-shadow:inset 0 0 0 5px rgba(161,136,127,.18),0 0 0 3px rgba(161,136,127,.12); } .owner-canvas.is-editing::after { content:'Mode atur layout: seret meja atau elemen untuk memindahkan posisinya'; position:absolute; z-index:12; right:.7rem; bottom:.7rem; border-radius:.65rem; background:rgba(62,39,35,.86); color:#f5f5dc; padding:.4rem .55rem; font-size:.62rem; font-weight:700; pointer-events:none; }
    .owner-marker { position:absolute; z-index:1; transform:translate(-50%,-50%); display:flex; align-items:center; justify-content:center; border:1px dashed rgba(141,110,99,.55); border-radius:.55rem; background:rgba(255,255,255,.76); color:#705a52; font-family:inherit; font-size:.56rem; font-weight:800; text-transform:uppercase; pointer-events:none; } .owner-marker.counter { min-width:8rem; padding:.4rem .6rem; border-style:solid; border-color:#5d4037; background:#5d4037; color:#fff; } .owner-marker.window { min-height:6rem; padding:.35rem .15rem; border-color:#8bb9d3; background:rgba(223,243,251,.8); color:#52728c; writing-mode:vertical-rl; } .owner-marker.entrance { min-width:6.5rem; padding:.35rem .5rem; border-color:#d3b27c; background:#fff7e9; color:#72552e; } .owner-canvas.is-editing .owner-marker { cursor:grab; pointer-events:auto; touch-action:none; } .owner-marker.is-selected { z-index:7; outline:3px solid rgba(62,39,35,.24); outline-offset:3px; box-shadow:0 6px 14px rgba(62,39,35,.2); } .owner-marker.is-dragging { cursor:grabbing !important; opacity:.76; }
    .owner-table { --table-color:#7a818c; position:absolute; z-index:3; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:.15rem; border:2px solid var(--table-color); background:#fff; color:#3e2723; box-shadow:0 6px 14px rgba(62,39,35,.12); cursor:pointer; transform:rotate(var(--table-rotation,0deg)); transition:box-shadow .16s ease,transform .16s ease; } .owner-table:hover,.owner-table:focus-visible,.owner-table.is-selected { z-index:6; outline:none; box-shadow:0 0 0 4px rgba(62,39,35,.14),0 10px 20px rgba(62,39,35,.18); } .owner-table:hover { transform:rotate(var(--table-rotation,0deg)) translateY(-2px); } .owner-table.is-dragging { cursor:grabbing; opacity:.76; transition:none; } .owner-canvas.is-editing .owner-table { cursor:grab; } .owner-table.round { border-radius:999px; } .owner-table.square { border-radius:.75rem; } .owner-table.rectangle { border-radius:.65rem; } .owner-table.inactive { border-style:dashed; opacity:.57; } .owner-table.available { --table-color:var(--available); background:var(--available-soft); } .owner-table.occupied { --table-color:var(--occupied); background:var(--occupied-soft); } .owner-table.reserved { --table-color:var(--reserved); background:var(--reserved-soft); } .owner-table.unavailable { --table-color:var(--unavailable); background:var(--unavailable-soft); } .owner-table-code { font-size:clamp(.57rem,1.1vw,.76rem); font-weight:850; line-height:1; } .owner-table-seat { color:#6d5b55; font-size:clamp(.45rem,.9vw,.58rem); font-weight:650; line-height:1; }
    .owner-property { padding:1rem; } .owner-property-empty { color:#8d6e63; font-size:.75rem; line-height:1.6; text-align:center; padding:1.25rem .5rem; } .owner-property-name { color:#3e2723; font-size:1rem; font-weight:850; } .owner-property-meta { margin-top:.2rem; color:#8d6e63; font-size:.7rem; } .owner-chip { display:inline-flex; border-radius:999px; padding:.32rem .52rem; font-size:.62rem; font-weight:800; } .owner-chip.available { color:var(--available); background:var(--available-soft); } .owner-chip.occupied { color:var(--occupied); background:var(--occupied-soft); } .owner-chip.reserved { color:var(--reserved); background:var(--reserved-soft); } .owner-chip.unavailable { color:var(--unavailable); background:var(--unavailable-soft); } .owner-property-actions { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.55rem; margin-top:1rem; } .owner-property-actions button { border:1px solid rgba(215,204,200,.76); border-radius:.7rem; background:#fff; color:#5d4037; padding:.6rem; font-size:.68rem; font-weight:800; transition:all .18s ease; } .owner-property-actions button:hover { border-color:#a1887f; background:#faf7f4; } .owner-property-actions button.archive { color:#b4533e; } .owner-property-actions button.delete { color:#b42318; border-color:rgba(180,35,24,.32); background:#fff7f5; } .owner-property-actions button.delete:hover { border-color:#b42318; background:#ffebe8; } .owner-marker-position-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.6rem; margin-top:1rem; } .owner-marker-position-submit { grid-column:span 2; width:100%; margin-top:.1rem; border-radius:.7rem; background:#3e2723; color:#f5f5dc; padding:.62rem; font-size:.68rem; font-weight:800; }
    .owner-editor-bar { display:none; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:.7rem; border-top:1px solid rgba(215,204,200,.55); background:#fffaf6; padding:.8rem 1rem; } .owner-editor-bar.is-visible { display:flex; } .owner-dirty { color:#b36c22; font-size:.68rem; font-weight:750; } .owner-preview-buttons { display:flex; gap:.4rem; } .owner-preview-button { border:1px solid rgba(215,204,200,.8); border-radius:.6rem; color:#8d6e63; background:#fff; padding:.4rem .55rem; font-size:.64rem; font-weight:750; } .owner-preview-button.is-active { border-color:#5d4037; background:#5d4037; color:#f5f5dc; }
    .owner-history { margin-top:1rem; } .owner-history.hidden,.owner-monitor.hidden,.owner-modal.hidden { display:none; } .owner-filter-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.65rem; padding:1rem; border-bottom:1px solid rgba(215,204,200,.55); } .owner-field-label { display:block; margin-bottom:.28rem; color:#6d5b55; font-size:.65rem; font-weight:800; } .owner-input { width:100%; border:1px solid rgba(215,204,200,.8); border-radius:.7rem; color:#3e2723; background:#fff; padding:.58rem .62rem; font-size:.72rem; outline:none; } .owner-input:focus { border-color:#a1887f; box-shadow:0 0 0 3px rgba(161,136,127,.13); } .owner-history-list { min-height:9rem; } .owner-history-item { display:flex; align-items:flex-start; justify-content:space-between; gap:.75rem; border-bottom:1px solid rgba(215,204,200,.38); padding:.85rem 1rem; } .owner-history-item:last-child { border-bottom:0; } .owner-history-main { min-width:0; color:#3e2723; font-size:.72rem; font-weight:750; } .owner-history-detail { margin-top:.18rem; color:#8d6e63; font-size:.66rem; line-height:1.45; } .owner-history-time { flex:none; color:#a1887f; font-size:.62rem; text-align:right; } .owner-empty { color:#8d6e63; font-size:.74rem; text-align:center; padding:2rem 1rem; }
    .owner-modal { position:fixed; inset:0; z-index:70; display:flex; align-items:end; justify-content:center; padding:1rem; background:rgba(27,15,11,.47); backdrop-filter:blur(4px); } .owner-modal-box { width:100%; max-width:34rem; max-height:calc(100vh - 2rem); overflow:auto; border:1px solid rgba(255,255,255,.45); border-radius:1.2rem; background:#fff; box-shadow:0 24px 64px rgba(27,15,11,.3); } .owner-modal-header { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; border-bottom:1px solid rgba(215,204,200,.55); padding:1rem 1.1rem; } .owner-modal-title { color:#3e2723; font-size:.98rem; font-weight:850; } .owner-modal-subtitle { margin-top:.15rem; color:#8d6e63; font-size:.68rem; } .owner-modal-close { width:2rem; height:2rem; border-radius:.65rem; color:#8d6e63; font-size:1.2rem; } .owner-modal-close:hover { background:#f5efeb; color:#3e2723; } .owner-modal-body { padding:1rem 1.1rem 1.1rem; } .owner-form-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.7rem; } .owner-form-span { grid-column:span 2; } .owner-modal-actions { display:flex; gap:.6rem; justify-content:flex-end; margin-top:1rem; } .owner-modal-actions button { border-radius:.7rem; padding:.62rem .8rem; font-size:.72rem; font-weight:800; } .owner-button-cancel { border:1px solid rgba(215,204,200,.8); color:#6d5b55; background:#fff; } .owner-button-save { background:#3e2723; color:#f5f5dc; } .owner-button-save:disabled { cursor:wait; opacity:.6; } .owner-note { min-height:4.5rem; resize:vertical; }
    .owner-toast { position:fixed; z-index:90; right:1rem; bottom:1rem; max-width:min(24rem,calc(100vw - 2rem)); border:1px solid; border-radius:.9rem; padding:.8rem .9rem; box-shadow:0 14px 34px rgba(62,39,35,.2); font-size:.74rem; font-weight:700; opacity:0; pointer-events:none; transform:translateY(1rem); transition:all .2s ease; } .owner-toast.show { opacity:1; transform:translateY(0); } .owner-toast.success { color:#0f6a58; border-color:#bde8db; background:#effbf6; } .owner-toast.error { color:#a14232; border-color:#ffd1c8; background:#fff6f4; } .owner-toast.info { color:#72552e; border-color:#efd6a5; background:#fffaf0; }
    @media (max-width:639px) { .owner-intro { border-radius:1.05rem; padding:1rem; } .owner-mode-tabs { width:100%; } .owner-mode-tab { flex:1; text-align:center; } .owner-stat-grid { gap:.6rem; } .owner-stat { padding:.78rem; } .owner-stat:last-child { grid-column:span 2; } .owner-card { border-radius:1rem; } .owner-card-header { align-items:flex-start; padding:.85rem .9rem; } .owner-canvas-scroll { overflow:hidden; padding:.5rem; } .owner-canvas { width:100%; min-width:0; } .owner-preview-mobile .owner-canvas { width:min(100%,390px); min-width:0; } .owner-canvas.is-editing::after { right:.5rem; bottom:.5rem; max-width:75%; font-size:.56rem; } .owner-editor-bar { align-items:stretch; padding:.75rem .9rem; } .owner-editor-bar > .flex { width:100%; } .owner-editor-bar .owner-action { flex:1; } .owner-preview-buttons { width:100%; } .owner-preview-button { flex:1; } .owner-property { padding:.9rem; } .owner-filter-grid,.owner-form-grid { grid-template-columns:1fr; padding:.9rem; } .owner-form-span { grid-column:auto; } .owner-history-item { align-items:stretch; flex-direction:column; gap:.35rem; } .owner-history-time { text-align:left; } .owner-modal { padding:.5rem; } .owner-modal-box { max-height:calc(100dvh - 1rem); border-radius:1rem; } .owner-modal-header { padding:.9rem .95rem; } .owner-modal-body { padding:.9rem .95rem .95rem; } .owner-modal-actions { justify-content:stretch; } .owner-modal-actions button { flex:1; } }
    @media (min-width:640px) and (max-width:1279px) { .owner-layout-grid { grid-template-columns:1fr; } .owner-canvas { width:100%; min-width:0; } .owner-filter-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media (min-width:768px) { .owner-stat-grid { grid-template-columns:repeat(5,minmax(0,1fr)); } .owner-modal { align-items:center; } }
    @media (min-width:1280px) { .owner-layout-grid { grid-template-columns:minmax(0,1fr) 17rem; } .owner-history { grid-column:span 2; } .owner-filter-grid { grid-template-columns:repeat(5,minmax(0,1fr)); } }
    /* Furnitur coffee shop: warna status tetap jelas, dengan meja bernuansa kayu dan kursi sesuai kapasitas. */
    .owner-table { --table-soft:var(--unavailable-soft); overflow:visible; isolation:isolate; border:3px solid var(--table-color); background:radial-gradient(circle at 28% 22%,rgba(255,234,197,.72) 0 7%,transparent 8%),repeating-linear-gradient(145deg,rgba(255,236,204,.17) 0 3px,rgba(93,58,26,.12) 4px 8px),linear-gradient(135deg,#c88955,#774027); box-shadow:0 7px 15px rgba(62,39,35,.22),inset 0 0 0 2px rgba(255,239,210,.22); }
    .owner-table.available { --table-soft:var(--available-soft); } .owner-table.occupied { --table-soft:var(--occupied-soft); } .owner-table.reserved { --table-soft:var(--reserved-soft); } .owner-table.unavailable { --table-soft:var(--unavailable-soft); }
    .owner-table::after { content:''; position:absolute; z-index:0; inset:4px; border:1px solid rgba(92,49,28,.42); border-radius:inherit; box-shadow:inset 0 0 0 1px rgba(255,239,210,.18); pointer-events:none; } .owner-table-code,.owner-table-seat { position:relative; z-index:2; } .owner-table-code { color:#1f1713; text-shadow:0 1px 0 rgba(255,255,255,.48); } .owner-table-seat { color:rgba(31,23,19,.86); } .owner-chairs { position:absolute; inset:0; z-index:1; pointer-events:none; } .owner-chair { position:absolute; width:clamp(.48rem,1.18vw,.86rem); height:clamp(.42rem,1vw,.72rem); border:1.5px solid #64371f; border-radius:.14rem .14rem .28rem .28rem; background:linear-gradient(135deg,#f0c288,#ae6439); box-shadow:0 2px 3px rgba(62,39,35,.28); transform:translate(-50%,-50%) rotate(var(--chair-rotation,0deg)); } .owner-chair::before { content:''; position:absolute; left:-11%; top:-78%; width:122%; height:72%; border:1.5px solid #64371f; border-bottom:0; border-radius:.28rem .28rem 0 0; background:linear-gradient(135deg,#e7ad71,#8f4b2c); } .owner-chair::after { content:''; position:absolute; left:16%; bottom:-48%; width:68%; height:44%; border-left:1.5px solid #64371f; border-right:1.5px solid #64371f; }
    .owner-editor-tools { display:flex; flex:1 1 100%; flex-wrap:wrap; align-items:center; gap:.55rem; } .owner-editor-tools-label { color:#6d5b55; font-size:.67rem; font-weight:800; } .owner-element-actions { display:flex; flex-wrap:wrap; gap:.4rem; } .owner-element-action { border:1px solid rgba(161,136,127,.52); border-radius:.6rem; background:#fff; color:#5d4037; padding:.4rem .55rem; font-size:.64rem; font-weight:800; transition:all .18s ease; } .owner-element-action:hover { border-color:#5d4037; background:#f8f1e8; } .owner-marker-position-remove { grid-column:span 2; width:100%; border:1px solid rgba(180,35,24,.32); border-radius:.7rem; background:#fff7f5; color:#b42318; padding:.62rem; font-size:.68rem; font-weight:800; }
    @media (max-width:639px) { .owner-editor-tools,.owner-element-actions { width:100%; } .owner-element-action { flex:1; } }
    /* Skala teks dan kursi selalu mengikuti sisi terpendek meja. */
    .owner-table { container-type:size; gap:clamp(.02rem,3cqmin,.15rem); } .owner-table::before { width:clamp(.18rem,10cqmin,.48rem); height:clamp(.18rem,10cqmin,.48rem); } .owner-table-code,.owner-table-name,.owner-table-seat { position:relative; z-index:2; max-width:88%; line-height:1; } .owner-table-code { font-size:clamp(.28rem,15cqmin,.76rem); } .owner-table-name { overflow:hidden; color:rgba(31,23,19,.9); font-size:clamp(.22rem,11cqmin,.57rem); font-weight:750; text-align:center; text-overflow:ellipsis; white-space:nowrap; } .owner-table-seat { font-size:clamp(.2rem,10cqmin,.58rem); } .owner-chair { width:clamp(.21rem,20cqmin,.96rem); height:clamp(.18rem,16cqmin,.8rem); border-width:clamp(.5px,3cqmin,1.5px); box-shadow:0 clamp(.3px,2cqmin,2px) clamp(.4px,3cqmin,3px) rgba(62,39,35,.28); } .owner-chair::before { border-width:clamp(.5px,3cqmin,1.5px); border-bottom:0; } .owner-chair::after { border-left-width:clamp(.5px,3cqmin,1.5px); border-right-width:clamp(.5px,3cqmin,1.5px); }
</style>
@endpush

@section('content')
<div class='owner-workspace'>
    <section class='owner-intro'>
        <div>
            <p class='text-[0.66rem] font-extrabold uppercase tracking-[0.15em] text-caramel'>Kontrol Operasional</p>
            <h3 id='owner-layout-name' class='mt-1 font-sans text-2xl font-bold text-espresso'>Manajemen meja</h3>
            <p id='owner-layout-description' class='mt-2 max-w-2xl text-sm leading-6 text-caramel-dark'>Memuat denah dan aktivitas meja.</p>
            <p id='owner-layout-updated' class='owner-updated'>Menunggu data denah...</p>
        </div>
        <div class='owner-mode-tabs' aria-label='Mode tampilan'>
            <button type='button' class='owner-mode-tab is-active' data-owner-mode='monitor'>Monitoring</button>
            <button type='button' class='owner-mode-tab' data-owner-mode='history'>Riwayat</button>
        </div>
    </section>

    <section class='owner-stat-grid' aria-live='polite'>
        <div class='owner-stat'><p class='owner-stat-label'>Total</p><p id='owner-stat-total' class='owner-stat-value'>–</p></div>
        <div class='owner-stat'><p class='owner-stat-label'><span class='owner-dot dot-available'></span>Tersedia</p><p id='owner-stat-available' class='owner-stat-value'>–</p></div>
        <div class='owner-stat'><p class='owner-stat-label'><span class='owner-dot dot-occupied'></span>Terisi</p><p id='owner-stat-occupied' class='owner-stat-value'>–</p></div>
        <div class='owner-stat'><p class='owner-stat-label'><span class='owner-dot dot-reserved'></span>Dipesan</p><p id='owner-stat-reserved' class='owner-stat-value'>–</p></div>
        <div class='owner-stat'><p class='owner-stat-label'><span class='owner-dot dot-unavailable'></span>Tidak tersedia</p><p id='owner-stat-unavailable' class='owner-stat-value'>–</p></div>
    </section>

    <section id='owner-monitor' class='owner-monitor owner-layout-grid'>
        <div class='owner-card'>
            <div class='owner-card-header'>
                <div><h4 class='owner-card-title'>Denah area</h4><p id='owner-board-caption' class='owner-card-subtitle'>Klik meja untuk melihat detail dan tindakan.</p></div>
                <div class='owner-preview-buttons'><button type='button' class='owner-preview-button is-active' data-preview='desktop'>Desktop</button><button type='button' class='owner-preview-button' data-preview='mobile'>Mobile</button></div>
            </div>
            <div id='owner-canvas-shell' class='owner-canvas-scroll'>
                <div id='owner-canvas' class='owner-canvas has-grid' role='region' aria-label='Denah meja pemilik'></div>
            </div>
            <div id='owner-editor-bar' class='owner-editor-bar'>
                <div class='owner-editor-tools'>
                    <span class='owner-editor-tools-label'>Tambah elemen denah:</span>
                    <div class='owner-element-actions'>
                        <button type='button' class='owner-element-action' data-add-marker='counter'>+ Kasir / Bar</button>
                        <button type='button' class='owner-element-action' data-add-marker='window'>+ Jendela</button>
                        <button type='button' class='owner-element-action' data-add-marker='entrance'>+ Pintu</button>
                    </div>
                </div>
                <span id='owner-dirty-text' class='owner-dirty'>Mode atur layout aktif.</span>
                <div class='flex gap-2'><button id='owner-reset-layout' type='button' class='owner-action owner-action-light'>Batalkan</button><button id='owner-save-layout' type='button' class='owner-action owner-action-dark'>Simpan layout</button></div>
            </div>
        </div>

        <aside class='owner-card'>
            <div class='owner-card-header'><div><h4 class='owner-card-title'>Detail denah</h4><p class='owner-card-subtitle'>Pilih meja atau elemen denah.</p></div></div>
            <div id='owner-property' class='owner-property'><p class='owner-property-empty'>Pilih meja atau elemen denah untuk melihat pengaturannya.</p></div>
        </aside>
    </section>

    <section id='owner-history' class='owner-history owner-card hidden'>
        <div class='owner-card-header'><div><h4 class='owner-card-title'>Riwayat perubahan status</h4><p class='owner-card-subtitle'>Audit perubahan meja oleh pemilik maupun karyawan.</p></div><button id='owner-history-refresh' type='button' class='owner-action owner-action-light'>Terapkan filter</button></div>
        <div class='owner-filter-grid'>
            <div><label class='owner-field-label' for='history-table'>Meja</label><select id='history-table' class='owner-input'><option value=''>Semua meja</option></select></div>
            <div><label class='owner-field-label' for='history-status'>Status baru</label><select id='history-status' class='owner-input'><option value=''>Semua status</option><option value='available'>Tersedia</option><option value='occupied'>Terisi</option><option value='reserved'>Dipesan</option><option value='unavailable'>Tidak tersedia</option></select></div>
            <div><label class='owner-field-label' for='history-from'>Dari tanggal</label><input id='history-from' class='owner-input' type='date'></div>
            <div><label class='owner-field-label' for='history-to'>Sampai tanggal</label><input id='history-to' class='owner-input' type='date'></div>
            <div class='flex items-end'><button id='owner-history-clear' type='button' class='owner-action owner-action-light w-full'>Reset filter</button></div>
        </div>
        <div id='owner-history-list' class='owner-history-list'><p class='owner-empty'>Pilih Riwayat untuk memuat audit perubahan.</p></div>
        <div class='flex justify-between gap-3 border-t border-latte/40 px-4 py-3'><button id='history-previous' type='button' class='owner-action owner-action-light'>Sebelumnya</button><span id='history-page' class='self-center text-xs font-semibold text-caramel'>–</span><button id='history-next' type='button' class='owner-action owner-action-light'>Berikutnya</button></div>
    </section>
</div>

<div id='owner-toast' class='owner-toast' role='status' aria-live='polite'></div>
@endsection

@push('modals')
<div id='owner-table-modal' class='owner-modal hidden' aria-hidden='true'>
    <div class='owner-modal-box' role='dialog' aria-modal='true' aria-labelledby='owner-table-modal-title'>
        <div class='owner-modal-header'><div><h3 id='owner-table-modal-title' class='owner-modal-title'>Tambah meja</h3><p id='owner-table-modal-subtitle' class='owner-modal-subtitle'>Atur data dan posisi awal meja.</p></div><button type='button' class='owner-modal-close' data-close-modal='owner-table-modal' aria-label='Tutup'>&times;</button></div>
        <form id='owner-table-form' class='owner-modal-body'>
            <div class='owner-form-grid'>
                <div><label class='owner-field-label' for='table-code'>Kode meja</label><input id='table-code' class='owner-input' maxlength='30' required></div>
                <div><label class='owner-field-label' for='table-name'>Nama meja</label><input id='table-name' class='owner-input' maxlength='255' required></div>
                <div><label class='owner-field-label' for='table-capacity'>Kapasitas</label><input id='table-capacity' class='owner-input' type='number' min='1' max='20' required></div>
                <div><label class='owner-field-label' for='table-shape'>Bentuk</label><select id='table-shape' class='owner-input'><option value='round'>Bulat</option><option value='square'>Persegi</option><option value='rectangle'>Persegi panjang</option></select></div>
                <div><label class='owner-field-label' for='table-x'>Posisi X</label><input id='table-x' class='owner-input' type='number' min='0' max='100' step='0.01' required></div>
                <div><label class='owner-field-label' for='table-y'>Posisi Y</label><input id='table-y' class='owner-input' type='number' min='0' max='100' step='0.01' required></div>
                <div><label class='owner-field-label' for='table-width'>Lebar</label><input id='table-width' class='owner-input' type='number' min='1' max='100' step='0.01' required></div>
                <div><label class='owner-field-label' for='table-height'>Tinggi</label><input id='table-height' class='owner-input' type='number' min='1' max='100' step='0.01' required></div>
                <div><label class='owner-field-label' for='table-rotation'>Rotasi</label><input id='table-rotation' class='owner-input' type='number' min='0' max='359.99' step='0.01' required></div>
                <div><label class='owner-field-label' for='table-active'>Tampilan</label><select id='table-active' class='owner-input'><option value='true'>Aktif</option><option value='false'>Arsip/nonaktif</option></select></div>
            </div>
            <div class='owner-modal-actions'><button type='button' class='owner-button-cancel' data-close-modal='owner-table-modal'>Batal</button><button id='owner-table-submit' type='submit' class='owner-button-save'>Simpan meja</button></div>
        </form>
    </div>
</div>

<div id='owner-status-modal' class='owner-modal hidden' aria-hidden='true'>
    <div class='owner-modal-box' role='dialog' aria-modal='true' aria-labelledby='owner-status-modal-title'>
        <div class='owner-modal-header'><div><h3 id='owner-status-modal-title' class='owner-modal-title'>Ubah status meja</h3><p id='owner-status-modal-subtitle' class='owner-modal-subtitle'></p></div><button type='button' class='owner-modal-close' data-close-modal='owner-status-modal' aria-label='Tutup'>&times;</button></div>
        <form id='owner-status-form' class='owner-modal-body'>
            <label class='owner-field-label' for='owner-status-select'>Status</label><select id='owner-status-select' class='owner-input'><option value='available'>Tersedia</option><option value='occupied'>Terisi</option><option value='reserved'>Dipesan</option><option value='unavailable'>Tidak tersedia</option></select>
            <label class='owner-field-label mt-3' for='owner-status-note'>Catatan operasional</label><textarea id='owner-status-note' class='owner-input owner-note' maxlength='500' placeholder='Opsional, maksimal 500 karakter'></textarea>
            <div class='owner-modal-actions'><button type='button' class='owner-button-cancel' data-close-modal='owner-status-modal'>Batal</button><button id='owner-status-submit' type='submit' class='owner-button-save'>Simpan status</button></div>
        </form>
    </div>
</div>
@endpush

@push('scripts')
<script>
(() => {
    const endpoints = {
        data: @json(route('pemilik.tables.data')),
        layout: @json(route('pemilik.tables.layout.update', ['floorLayout' => '__LAYOUT_ID__'])),
        store: @json(route('pemilik.tables.store')),
        update: @json(route('pemilik.tables.update', ['coffeeTable' => '__TABLE_ID__'])),
        destroy: @json(route('pemilik.tables.destroy', ['coffeeTable' => '__TABLE_ID__'])),
        active: @json(route('pemilik.tables.toggle-active', ['coffeeTable' => '__TABLE_ID__'])),
        status: @json(route('pemilik.tables.status.update', ['coffeeTable' => '__TABLE_ID__'])),
        history: @json(route('pemilik.tables.history')),
    };
    const csrfToken = document.querySelector('meta[name=csrf-token]').getAttribute('content');
    const labels = { available: 'Tersedia', occupied: 'Terisi', reserved: 'Dipesan', unavailable: 'Tidak tersedia' };
    const state = { data: null, selectedId: null, selectedMarkerIndex: null, editing: false, dirty: false, preview: 'desktop', loading: false, dragging: null, tableFormMode: 'create', statusTableId: null, historyPage: 1, historyMeta: null, toastTimer: null };
    const elements = {
        refresh: document.getElementById('owner-refresh'), edit: document.getElementById('owner-edit-toggle'), add: document.getElementById('owner-add-table'), canvas: document.getElementById('owner-canvas'), canvasShell: document.getElementById('owner-canvas-shell'), property: document.getElementById('owner-property'), editorBar: document.getElementById('owner-editor-bar'), dirty: document.getElementById('owner-dirty-text'), saveLayout: document.getElementById('owner-save-layout'), resetLayout: document.getElementById('owner-reset-layout'), toast: document.getElementById('owner-toast'), monitor: document.getElementById('owner-monitor'), history: document.getElementById('owner-history'), historyList: document.getElementById('owner-history-list'), tableModal: document.getElementById('owner-table-modal'), statusModal: document.getElementById('owner-status-modal'), tableForm: document.getElementById('owner-table-form'), statusForm: document.getElementById('owner-status-form'),
    };

    const number = (value, fallback = 0) => Number.isFinite(Number(value)) ? Number(value) : fallback;
    const clamp = (value, min, max) => Math.min(max, Math.max(min, value));
    const round = (value) => Math.round(value * 100) / 100;
    const findTable = (id) => state.data?.tables.find((table) => Number(table.id) === Number(id)) ?? null;
    const markerTypes = { counter: 'Kasir / bar', window: 'Jendela', entrance: 'Pintu' };
    const markerTemplates = {
        counter: { label: 'Kasir & Bar', position_x: 50, position_y: 9 },
        window: { label: 'Jendela', position_x: 94, position_y: 35 },
        entrance: { label: 'Pintu Masuk', position_x: 35, position_y: 94 },
    };
    const backgroundElements = () => {
        const elements = state.data?.layout?.background_config?.elements;
        return Array.isArray(elements) ? elements : [];
    };
    const findMarker = (index) => index !== null && index !== undefined && Number.isInteger(Number(index)) ? backgroundElements()[Number(index)] ?? null : null;
    const isSelectedMarker = (index) => state.selectedMarkerIndex !== null && Number(state.selectedMarkerIndex) === Number(index);
    const statusLabel = (status) => labels[status] ?? 'Tidak diketahui';

    function formatDate(value) {
        if (!value) return 'Belum ada pembaruan.';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return 'Waktu tidak tersedia.';
        return new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(date);
    }

    function showToast(message, type = 'info') {
        window.clearTimeout(state.toastTimer);
        elements.toast.textContent = message;
        elements.toast.className = 'owner-toast ' + type + ' show';
        state.toastTimer = window.setTimeout(() => elements.toast.classList.remove('show'), 4500);
    }

    function setLoading(isLoading) {
        state.loading = isLoading;
        elements.refresh.disabled = isLoading;
    }

    function setText(id, value) { document.getElementById(id).textContent = value; }

    function renderSummary(summary) {
        setText('owner-stat-total', summary.total ?? 0);
        setText('owner-stat-available', summary.available ?? 0);
        setText('owner-stat-occupied', summary.occupied ?? 0);
        setText('owner-stat-reserved', summary.reserved ?? 0);
        setText('owner-stat-unavailable', summary.unavailable ?? 0);
    }

    function renderMarker(marker, index) {
        const type = Object.prototype.hasOwnProperty.call(markerTypes, marker.type) ? marker.type : 'marker';
        const item = document.createElement(state.editing ? 'button' : 'div');
        item.className = 'owner-marker ' + type + (isSelectedMarker(index) ? ' is-selected' : '');
        item.style.left = number(marker.position_x, 50) + '%';
        item.style.top = number(marker.position_y, 50) + '%';
        item.textContent = marker.label ?? 'Area';
        if (state.editing) {
            item.type = 'button';
            item.title = (marker.label ?? markerTypes[type] ?? 'Elemen denah') + ' - seret untuk memindahkan';
            item.setAttribute('aria-label', item.title);
            item.addEventListener('click', () => { if (!state.dragging) selectMarker(index); });
            item.addEventListener('pointerdown', (event) => startMarkerDrag(event, index, item));
        }
        elements.canvas.appendChild(item);
    }

    function renderChairs(button, capacity) {
        const chairs = document.createElement('span');
        const seatCount = Math.max(1, Math.min(20, Math.round(number(capacity, 1))));
        chairs.className = 'owner-chairs';
        chairs.setAttribute('aria-hidden', 'true');
        for (let index = 0; index < seatCount; index += 1) {
            const angle = (Math.PI * 2 * index) / seatCount - Math.PI / 2;
            const chair = document.createElement('span');
            chair.className = 'owner-chair';
            chair.style.left = (50 + Math.cos(angle) * 66) + '%';
            chair.style.top = (50 + Math.sin(angle) * 70) + '%';
            chair.style.setProperty('--chair-rotation', (angle * 180 / Math.PI + 90) + 'deg');
            chairs.appendChild(chair);
        }
        button.appendChild(chairs);
    }

    function renderTable(table) {
        const shapes = ['round', 'square', 'rectangle'];
        const statuses = ['available', 'occupied', 'reserved', 'unavailable'];
        const button = document.createElement('button');
        const code = document.createElement('span');
        const name = document.createElement('span');
        const seats = document.createElement('span');
        const shape = shapes.includes(table.shape) ? table.shape : 'square';
        const status = statuses.includes(table.status) ? table.status : 'unavailable';
        button.type = 'button';
        button.dataset.tableId = table.id;
        button.className = 'owner-table ' + shape + ' ' + status + (!table.is_active ? ' inactive' : '') + (Number(state.selectedId) === Number(table.id) ? ' is-selected' : '');
        button.style.left = number(table.position_x) + '%'; button.style.top = number(table.position_y) + '%'; button.style.width = number(table.width) + '%'; button.style.height = number(table.height) + '%'; button.style.setProperty('--table-rotation', number(table.rotation) + 'deg');
        button.title = table.name + ' · ' + statusLabel(status) + (!table.is_active ? ' · Diarsipkan' : '');
        button.setAttribute('aria-label', table.code + ', ' + table.name + ', ' + table.capacity + ' kursi, ' + statusLabel(status));
        code.className = 'owner-table-code'; code.textContent = table.code;
        name.className = 'owner-table-name'; name.textContent = table.name;
        seats.className = 'owner-table-seat'; seats.textContent = table.capacity + ' kursi';
        renderChairs(button, table.capacity);
        button.append(code, name, seats);
        button.addEventListener('click', () => { if (!state.dragging) selectTable(table.id); });
        button.addEventListener('pointerdown', (event) => startDrag(event, table, button));
        elements.canvas.appendChild(button);
    }

    function renderCanvas() {
        if (!state.data) return;
        const layout = state.data.layout;
        const config = layout.background_config ?? {};
        elements.canvas.replaceChildren();
        elements.canvas.style.aspectRatio = String(layout.canvas_width ?? 1200) + ' / ' + String(layout.canvas_height ?? 800);
        elements.canvas.classList.toggle('has-grid', config.show_grid !== false);
        elements.canvas.classList.toggle('is-editing', state.editing);
        backgroundElements().forEach(renderMarker);
        state.data.tables.forEach(renderTable);
    }

    function renderProperty() {
        const marker = findMarker(state.selectedMarkerIndex);
        const table = findTable(state.selectedId);
        elements.property.replaceChildren();
        if (marker) {
            const title = document.createElement('p'); title.className = 'owner-property-name'; title.textContent = marker.label ?? markerTypes[marker.type] ?? 'Elemen denah';
            const meta = document.createElement('p'); meta.className = 'owner-property-meta'; meta.textContent = (markerTypes[marker.type] ?? 'Elemen denah') + '. Seret di denah atau masukkan koordinat presisi.';
            const form = document.createElement('form'); form.className = 'owner-marker-position-grid';
            const xField = document.createElement('label'); xField.className = 'owner-field-label'; xField.textContent = 'Posisi X';
            const xInput = document.createElement('input'); xInput.className = 'owner-input'; xInput.type = 'number'; xInput.min = '0'; xInput.max = '100'; xInput.step = '0.01'; xInput.value = number(marker.position_x, 50); xField.appendChild(xInput);
            const yField = document.createElement('label'); yField.className = 'owner-field-label'; yField.textContent = 'Posisi Y';
            const yInput = document.createElement('input'); yInput.className = 'owner-input'; yInput.type = 'number'; yInput.min = '0'; yInput.max = '100'; yInput.step = '0.01'; yInput.value = number(marker.position_y, 50); yField.appendChild(yInput);
            const apply = document.createElement('button'); apply.type = 'submit'; apply.className = 'owner-marker-position-submit'; apply.textContent = 'Terapkan posisi';
            const remove = document.createElement('button'); remove.type = 'button'; remove.className = 'owner-marker-position-remove'; remove.textContent = 'Hapus elemen'; remove.addEventListener('click', () => removeMarker(state.selectedMarkerIndex));
            form.append(xField, yField, apply, remove);
            form.addEventListener('submit', (event) => {
                event.preventDefault();
                marker.position_x = round(clamp(number(xInput.value, marker.position_x), 0, 100));
                marker.position_y = round(clamp(number(yInput.value, marker.position_y), 0, 100));
                state.dirty = true;
                renderAll();
            });
            elements.property.append(title, meta, form); return;
        }
        if (!table) {
            const empty = document.createElement('p'); empty.className = 'owner-property-empty'; empty.textContent = state.editing ? 'Pilih meja atau elemen denah untuk mengatur posisinya.' : 'Pilih meja untuk melihat status, petugas terakhir, serta tindakan pengelolaan.'; elements.property.appendChild(empty); return;
        }
        const title = document.createElement('p'); title.className = 'owner-property-name'; title.textContent = table.code + ' · ' + table.name;
        const meta = document.createElement('p'); meta.className = 'owner-property-meta'; meta.textContent = table.capacity + ' kursi · ' + (table.shape === 'round' ? 'Bulat' : table.shape === 'rectangle' ? 'Persegi panjang' : 'Persegi');
        const chip = document.createElement('span'); chip.className = 'owner-chip ' + table.status; chip.textContent = statusLabel(table.status);
        const update = document.createElement('p'); update.className = 'owner-property-meta'; update.textContent = table.status_updated_at ? 'Diperbarui ' + formatDate(table.status_updated_at) + (table.status_updated_by?.name ? ' oleh ' + table.status_updated_by.name : '') : 'Belum ada pembaruan status.';
        const active = document.createElement('p'); active.className = 'owner-property-meta'; active.textContent = table.is_active ? 'Tampil untuk pelanggan dan karyawan.' : 'Diarsipkan: tidak tampil bagi pelanggan dan karyawan.';
        const actions = document.createElement('div'); actions.className = 'owner-property-actions';
        const edit = document.createElement('button'); edit.type = 'button'; edit.textContent = 'Ubah detail'; edit.addEventListener('click', () => openTableModal('edit', table));
        const status = document.createElement('button'); status.type = 'button'; status.textContent = 'Ubah status'; status.disabled = !table.is_active; status.addEventListener('click', () => openStatusModal(table));
        const toggle = document.createElement('button'); toggle.type = 'button'; toggle.className = 'archive'; toggle.textContent = table.is_active ? 'Arsipkan meja' : 'Aktifkan meja'; toggle.addEventListener('click', () => toggleActive(table));
        const remove = document.createElement('button'); remove.type = 'button'; remove.className = 'delete'; remove.textContent = 'Hapus meja'; remove.addEventListener('click', () => deleteTable(table));
        actions.append(edit, status, toggle, remove);
        elements.property.append(title, meta, chip, update, active, actions);
    }

    function renderTableFilter() {
        const select = document.getElementById('history-table');
        const selected = select.value;
        select.replaceChildren();
        const all = document.createElement('option'); all.value = ''; all.textContent = 'Semua meja'; select.appendChild(all);
        (state.data?.tables ?? []).forEach((table) => { const option = document.createElement('option'); option.value = table.id; option.textContent = table.code + ' · ' + table.name; select.appendChild(option); });
        select.value = selected;
    }

    function renderAll() {
        if (!state.data) return;
        const layout = state.data.layout;
        setText('owner-layout-name', layout.name ?? 'Manajemen meja');
        setText('owner-layout-description', layout.description ?? 'Denah meja Ciks Coffee.');
        setText('owner-layout-updated', 'Denah diperbarui ' + formatDate(layout.updated_at));
        renderSummary(state.data.summary);
        renderCanvas(); renderProperty(); renderTableFilter();
        elements.editorBar.classList.toggle('is-visible', state.editing);
        elements.edit.className = 'owner-action ' + (state.editing ? 'owner-action-warn' : 'owner-action-dark');
        elements.edit.textContent = state.editing ? 'Selesai mengatur' : 'Atur layout';
        elements.dirty.textContent = state.dirty ? 'Perubahan belum disimpan.' : 'Mode atur layout aktif.';
        document.getElementById('owner-board-caption').textContent = state.editing ? 'Seret meja, kasir/bar, jendela, atau pintu. Simpan setelah selesai.' : 'Klik meja untuk melihat detail dan tindakan.';
    }

    async function loadData({ quiet = false } = {}) {
        if (state.loading) return;
        setLoading(true);
        try {
            const response = await fetch(endpoints.data, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            const payload = await response.json().catch(() => null);
            if (!response.ok || !payload?.success) throw new Error(payload?.message ?? 'Data denah tidak dapat dimuat.');
            state.data = payload.data; state.selectedId = null; state.selectedMarkerIndex = null; state.dirty = false; renderAll();
            if (!quiet) showToast('Data denah sudah diperbarui.', 'success');
        } catch (error) { if (!quiet) showToast(error.message ?? 'Data denah tidak dapat dimuat.', 'error'); }
        finally { setLoading(false); }
    }

    function selectTable(id) {
        state.selectedId = id;
        state.selectedMarkerIndex = null;
        renderCanvas(); renderProperty();
    }

    function selectMarker(index) {
        state.selectedId = null;
        state.selectedMarkerIndex = index;
        renderCanvas(); renderProperty();
    }

    function addMarker(type) {
        if (!state.data || !Object.prototype.hasOwnProperty.call(markerTemplates, type)) return;
        const markers = backgroundElements();
        if (markers.length >= 20) { showToast('Maksimal 20 elemen denah dapat ditambahkan.', 'info'); return; }
        const template = markerTemplates[type];
        const sameTypeCount = markers.filter((marker) => marker.type === type).length;
        const offset = sameTypeCount * 12;
        const marker = {
            type,
            label: sameTypeCount ? template.label + ' ' + (sameTypeCount + 1) : template.label,
            position_x: type === 'entrance' ? clamp(template.position_x + offset, 0, 100) : template.position_x,
            position_y: type === 'window' ? clamp(template.position_y + offset, 0, 100) : template.position_y,
        };
        const config = state.data.layout.background_config ?? {};
        if (!Array.isArray(config.elements)) config.elements = [];
        config.elements.push(marker);
        state.data.layout.background_config = config;
        state.selectedId = null;
        state.selectedMarkerIndex = config.elements.length - 1;
        state.dirty = true;
        renderAll();
    }

    function removeMarker(index) {
        const marker = findMarker(index);
        if (!marker || !window.confirm('Hapus ' + (marker.label ?? 'elemen denah') + ' dari denah?')) return;
        backgroundElements().splice(Number(index), 1);
        state.selectedMarkerIndex = null;
        state.dirty = true;
        renderAll();
    }

    function startDrag(event, table, button) {
        if (!state.editing || event.button !== 0) return;
        event.preventDefault();
        const rect = elements.canvas.getBoundingClientRect();
        state.dragging = { kind: 'table', id: table.id, button, startClientX: event.clientX, startClientY: event.clientY, startX: number(table.position_x), startY: number(table.position_y), rect };
        button.classList.add('is-dragging');
        button.setPointerCapture?.(event.pointerId);
    }

    function startMarkerDrag(event, index, button) {
        if (!state.editing || event.button !== 0) return;
        const marker = findMarker(index);
        if (!marker) return;
        event.preventDefault();
        const rect = elements.canvas.getBoundingClientRect();
        state.dragging = { kind: 'marker', index, button, startClientX: event.clientX, startClientY: event.clientY, startX: number(marker.position_x, 50), startY: number(marker.position_y, 50), rect };
        button.classList.add('is-dragging');
        button.setPointerCapture?.(event.pointerId);
    }

    function moveDrag(event) {
        if (!state.dragging) return;
        const drag = state.dragging;
        const dx = ((event.clientX - drag.startClientX) / drag.rect.width) * 100;
        const dy = ((event.clientY - drag.startClientY) / drag.rect.height) * 100;
        if (drag.kind === 'marker') {
            const marker = findMarker(drag.index);
            if (!marker) return;
            marker.position_x = round(clamp(drag.startX + dx, 0, 100));
            marker.position_y = round(clamp(drag.startY + dy, 0, 100));
            drag.button.style.left = marker.position_x + '%';
            drag.button.style.top = marker.position_y + '%';
            state.dirty = true;
            elements.dirty.textContent = 'Perubahan belum disimpan.';
            return;
        }
        const table = findTable(drag.id);
        if (!table) return;
        table.position_x = round(clamp(drag.startX + dx, 0, 100 - number(table.width)));
        table.position_y = round(clamp(drag.startY + dy, 0, 100 - number(table.height)));
        drag.button.style.left = table.position_x + '%';
        drag.button.style.top = table.position_y + '%';
        state.dirty = true;
        elements.dirty.textContent = 'Perubahan belum disimpan.';
    }

    function endDrag() {
        if (!state.dragging) return;
        state.dragging.button.classList.remove('is-dragging');
        state.dragging = null;
        renderProperty();
    }

    function openModal(modal) { modal.classList.remove('hidden'); modal.setAttribute('aria-hidden', 'false'); }
    function closeModal(modal) { modal.classList.add('hidden'); modal.setAttribute('aria-hidden', 'true'); }
    function field(id) { return document.getElementById(id); }

    function openTableModal(mode, table = null) {
        state.tableFormMode = mode;
        field('owner-table-modal-title').textContent = mode === 'create' ? 'Tambah meja baru' : 'Ubah detail meja';
        field('owner-table-modal-subtitle').textContent = mode === 'create' ? 'Data posisi dapat disesuaikan kembali melalui editor layout.' : 'Perubahan detail memakai versi data terbaru.';
        field('owner-table-submit').textContent = mode === 'create' ? 'Tambah meja' : 'Simpan detail';
        elements.tableForm.dataset.tableId = table?.id ?? '';
        field('table-code').value = table?.code ?? '';
        field('table-name').value = table?.name ?? '';
        field('table-capacity').value = table?.capacity ?? 2;
        field('table-shape').value = table?.shape ?? 'round';
        field('table-x').value = table?.position_x ?? 43;
        field('table-y').value = table?.position_y ?? 43;
        field('table-width').value = table?.width ?? 13;
        field('table-height').value = table?.height ?? 13;
        field('table-rotation').value = table?.rotation ?? 0;
        field('table-active').value = String(table?.is_active ?? true);
        openModal(elements.tableModal);
        window.setTimeout(() => field('table-code').focus(), 60);
    }

    function openStatusModal(table) {
        state.statusTableId = table.id;
        field('owner-status-modal-title').textContent = 'Ubah status ' + table.code;
        field('owner-status-modal-subtitle').textContent = table.name + ' · versi ' + table.version;
        field('owner-status-select').value = table.status;
        field('owner-status-note').value = table.status_note ?? '';
        openModal(elements.statusModal);
    }

    function recalculateSummary() {
        const summary = { total: state.data.tables.length, available: 0, occupied: 0, reserved: 0, unavailable: 0 };
        state.data.tables.forEach((table) => { if (Object.prototype.hasOwnProperty.call(summary, table.status)) summary[table.status] += 1; });
        state.data.summary = summary;
    }

    function replaceTable(updatedTable) {
        if (!state.data || !updatedTable) return;
        const index = state.data.tables.findIndex((table) => Number(table.id) === Number(updatedTable.id));
        if (index >= 0) state.data.tables.splice(index, 1, updatedTable);
        else state.data.tables.push(updatedTable);
        recalculateSummary();
        renderAll();
    }

    async function requestJson(url, method = 'GET', body = null) {
        const options = { method, headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } };
        if (body !== null) {
            options.headers['Content-Type'] = 'application/json';
            options.headers['X-CSRF-TOKEN'] = csrfToken;
            options.body = JSON.stringify(body);
        }
        const response = await fetch(url, options);
        const payload = await response.json().catch(() => null);
        return { response, payload };
    }

    function conflictMessage(payload) {
        if (payload?.data) replaceTable(payload.data);
        showToast(payload?.message ?? 'Data meja telah diperbarui oleh pengguna lain. Muat ulang dan coba lagi.', 'info');
    }

    async function submitTableForm(event) {
        event.preventDefault();
        if (!state.data) return;
        const payload = {
            code: field('table-code').value.trim(), name: field('table-name').value.trim(), capacity: number(field('table-capacity').value), shape: field('table-shape').value,
            position_x: number(field('table-x').value), position_y: number(field('table-y').value), width: number(field('table-width').value), height: number(field('table-height').value), rotation: number(field('table-rotation').value), is_active: field('table-active').value === 'true',
        };
        const submit = field('owner-table-submit'); submit.disabled = true; submit.textContent = 'Menyimpan...';
        try {
            let result;
            if (state.tableFormMode === 'create') { payload.floor_layout_id = state.data.layout.id; result = await requestJson(endpoints.store, 'POST', payload); }
            else {
                const table = findTable(elements.tableForm.dataset.tableId);
                if (!table) throw new Error('Meja yang dipilih tidak ditemukan.');
                payload.version = table.version;
                result = await requestJson(endpoints.update.replace('__TABLE_ID__', String(table.id)), 'PUT', payload);
            }
            if (result.response.status === 409) { conflictMessage(result.payload); return; }
            if (!result.response.ok || !result.payload?.success) throw new Error(result.payload?.message ?? 'Data meja tidak dapat disimpan.');
            replaceTable(result.payload.data); closeModal(elements.tableModal); showToast(result.payload.message ?? 'Data meja berhasil disimpan.', 'success');
        } catch (error) { showToast(error.message ?? 'Data meja tidak dapat disimpan.', 'error'); }
        finally { submit.disabled = false; submit.textContent = state.tableFormMode === 'create' ? 'Tambah meja' : 'Simpan detail'; }
    }

    async function submitStatusForm(event) {
        event.preventDefault();
        const table = findTable(state.statusTableId);
        if (!table) return;
        const submit = field('owner-status-submit'); submit.disabled = true; submit.textContent = 'Menyimpan...';
        try {
            const result = await requestJson(endpoints.status.replace('__TABLE_ID__', String(table.id)), 'PATCH', { status: field('owner-status-select').value, note: field('owner-status-note').value.trim(), version: table.version });
            if (result.response.status === 409) { conflictMessage(result.payload); return; }
            if (!result.response.ok || !result.payload?.success) throw new Error(result.payload?.message ?? 'Status meja tidak dapat disimpan.');
            replaceTable(result.payload.data); closeModal(elements.statusModal); showToast(result.payload.message ?? 'Status meja berhasil diperbarui.', 'success');
        } catch (error) { showToast(error.message ?? 'Status meja tidak dapat disimpan.', 'error'); }
        finally { submit.disabled = false; submit.textContent = 'Simpan status'; }
    }

    async function toggleActive(table) {
        const action = table.is_active ? 'arsipkan' : 'aktifkan';
        if (!window.confirm('Yakin ingin ' + action + ' ' + table.code + '? Riwayat status tetap disimpan.')) return;
        try {
            const result = await requestJson(endpoints.active.replace('__TABLE_ID__', String(table.id)), 'PATCH', { is_active: !table.is_active, version: table.version });
            if (result.response.status === 409) { conflictMessage(result.payload); return; }
            if (!result.response.ok || !result.payload?.success) throw new Error(result.payload?.message ?? 'Status arsip meja tidak dapat diubah.');
            replaceTable(result.payload.data); showToast(result.payload.message ?? 'Status meja berhasil diperbarui.', 'success');
        } catch (error) { showToast(error.message ?? 'Status arsip meja tidak dapat diubah.', 'error'); }
    }

    async function deleteTable(table) {
        const message = 'Hapus ' + table.code + ' secara permanen? Tindakan ini tidak dapat dibatalkan. Meja yang sudah memiliki riwayat status harus diarsipkan, bukan dihapus.';
        if (!window.confirm(message)) return;
        try {
            const result = await requestJson(endpoints.destroy.replace('__TABLE_ID__', String(table.id)), 'DELETE', { version: table.version });
            if (result.response.status === 409) { conflictMessage(result.payload); return; }
            if (!result.response.ok || !result.payload?.success) throw new Error(result.payload?.message ?? 'Meja tidak dapat dihapus.');
            state.data.tables = state.data.tables.filter((item) => Number(item.id) !== Number(table.id));
            state.selectedId = null;
            state.dirty = false;
            recalculateSummary();
            renderAll();
            showToast(result.payload.message ?? 'Meja berhasil dihapus.', 'success');
        } catch (error) { showToast(error.message ?? 'Meja tidak dapat dihapus.', 'error'); }
    }

    async function saveLayout() {
        if (!state.data || !state.dirty) { showToast('Belum ada perubahan posisi yang perlu disimpan.', 'info'); return; }
        elements.saveLayout.disabled = true; elements.saveLayout.textContent = 'Menyimpan...';
        try {
            const config = state.data.layout.background_config ?? {};
            const payload = {
                background_config: {
                    ...config,
                    elements: backgroundElements().map((marker) => ({ type: marker.type, label: marker.label, position_x: number(marker.position_x, 50), position_y: number(marker.position_y, 50) })),
                },
                tables: state.data.tables.map((table) => ({ id: table.id, position_x: table.position_x, position_y: table.position_y, width: table.width, height: table.height, rotation: table.rotation, version: table.version })),
            };
            const result = await requestJson(endpoints.layout.replace('__LAYOUT_ID__', String(state.data.layout.id)), 'PUT', payload);
            if (result.response.status === 409) { conflictMessage(result.payload); return; }
            if (!result.response.ok || !result.payload?.success) throw new Error(result.payload?.message ?? 'Layout tidak dapat disimpan.');
            state.data = result.payload.data; state.dirty = false; renderAll(); showToast(result.payload.message ?? 'Denah meja berhasil disimpan.', 'success');
        } catch (error) { showToast(error.message ?? 'Layout tidak dapat disimpan.', 'error'); }
        finally { elements.saveLayout.disabled = false; elements.saveLayout.textContent = 'Simpan layout'; }
    }

    function toggleEditMode() {
        if (state.editing && state.dirty && !window.confirm('Perubahan posisi yang belum disimpan akan dibatalkan. Lanjutkan?')) return;
        if (state.editing && state.dirty) { state.editing = false; state.selectedMarkerIndex = null; loadData({ quiet: true }); return; }
        if (state.editing) state.selectedMarkerIndex = null;
        state.editing = !state.editing; renderAll();
    }

    function renderHistory(items, meta) {
        elements.historyList.replaceChildren();
        if (!items.length) { const empty = document.createElement('p'); empty.className = 'owner-empty'; empty.textContent = 'Belum ada perubahan status sesuai filter.'; elements.historyList.appendChild(empty); }
        items.forEach((item) => {
            const row = document.createElement('div'); row.className = 'owner-history-item';
            const left = document.createElement('div'); left.className = 'min-w-0';
            const main = document.createElement('p'); main.className = 'owner-history-main'; main.textContent = (item.coffee_table?.code ?? 'Meja') + ' · ' + statusLabel(item.old_status) + ' → ' + statusLabel(item.new_status);
            const detail = document.createElement('p'); detail.className = 'owner-history-detail'; detail.textContent = (item.changed_by?.name ?? 'Sistem') + (item.note ? ' · ' + item.note : '') + ' · ' + item.source;
            const time = document.createElement('p'); time.className = 'owner-history-time'; time.textContent = formatDate(item.changed_at);
            left.append(main, detail); row.append(left, time); elements.historyList.appendChild(row);
        });
        state.historyMeta = meta;
        setText('history-page', meta.total ? 'Halaman ' + meta.current_page + ' dari ' + meta.last_page : 'Tidak ada data');
        document.getElementById('history-previous').disabled = !meta.total || meta.current_page <= 1;
        document.getElementById('history-next').disabled = !meta.total || meta.current_page >= meta.last_page;
    }

    async function loadHistory(page = 1) {
        state.historyPage = page;
        const params = new URLSearchParams({ page: String(page), per_page: '15' });
        const table = field('history-table').value; const status = field('history-status').value; const from = field('history-from').value; const to = field('history-to').value;
        if (table) params.set('coffee_table_id', table); if (status) params.set('status', status); if (from) params.set('date_from', from); if (to) params.set('date_to', to);
        try {
            const result = await requestJson(endpoints.history + '?' + params.toString());
            if (!result.response.ok || !result.payload?.success) throw new Error(result.payload?.message ?? 'Riwayat tidak dapat dimuat.');
            renderHistory(result.payload.data, result.payload.meta);
        } catch (error) { showToast(error.message ?? 'Riwayat tidak dapat dimuat.', 'error'); }
    }

    function setMode(mode) {
        document.querySelectorAll('[data-owner-mode]').forEach((button) => button.classList.toggle('is-active', button.dataset.ownerMode === mode));
        elements.monitor.classList.toggle('hidden', mode !== 'monitor');
        elements.history.classList.toggle('hidden', mode !== 'history');
        if (mode === 'history') loadHistory(1);
    }

    elements.refresh.addEventListener('click', () => loadData());
    elements.add.addEventListener('click', () => { if (state.data) openTableModal('create'); else showToast('Tunggu denah selesai dimuat.', 'info'); });
    elements.edit.addEventListener('click', toggleEditMode);
    elements.saveLayout.addEventListener('click', saveLayout);
    elements.resetLayout.addEventListener('click', () => { if (!state.dirty || window.confirm('Batalkan seluruh perubahan posisi yang belum disimpan?')) loadData({ quiet: true }); });
    document.querySelectorAll('[data-add-marker]').forEach((button) => button.addEventListener('click', () => addMarker(button.dataset.addMarker)));
    elements.canvas.addEventListener('pointermove', moveDrag);
    elements.canvas.addEventListener('pointerup', endDrag);
    elements.canvas.addEventListener('pointercancel', endDrag);
    window.addEventListener('pointerup', endDrag);
    document.querySelectorAll('[data-owner-mode]').forEach((button) => button.addEventListener('click', () => setMode(button.dataset.ownerMode)));
    document.querySelectorAll('[data-preview]').forEach((button) => button.addEventListener('click', () => { state.preview = button.dataset.preview; elements.canvasShell.classList.toggle('owner-preview-mobile', state.preview === 'mobile'); document.querySelectorAll('[data-preview]').forEach((item) => item.classList.toggle('is-active', item.dataset.preview === state.preview)); }));
    elements.tableForm.addEventListener('submit', submitTableForm);
    elements.statusForm.addEventListener('submit', submitStatusForm);
    document.querySelectorAll('[data-close-modal]').forEach((button) => button.addEventListener('click', () => closeModal(document.getElementById(button.dataset.closeModal))));
    [elements.tableModal, elements.statusModal].forEach((modal) => modal.addEventListener('click', (event) => { if (event.target === modal) closeModal(modal); }));
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape') { closeModal(elements.tableModal); closeModal(elements.statusModal); } });
    document.getElementById('owner-history-refresh').addEventListener('click', () => loadHistory(1));
    document.getElementById('owner-history-clear').addEventListener('click', () => { field('history-table').value = ''; field('history-status').value = ''; field('history-from').value = ''; field('history-to').value = ''; loadHistory(1); });
    document.getElementById('history-previous').addEventListener('click', () => { if (state.historyMeta?.current_page > 1) loadHistory(state.historyMeta.current_page - 1); });
    document.getElementById('history-next').addEventListener('click', () => { if (state.historyMeta?.current_page < state.historyMeta?.last_page) loadHistory(state.historyMeta.current_page + 1); });
    loadData({ quiet: true });
})();
</script>
@endpush
