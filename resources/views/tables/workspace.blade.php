@extends($workspaceLayout)

@section('title', $workspaceTitle)
@section('page-title', $workspaceTitle)
@section('page-description', 'Satu denah untuk seluruh area Ciks Coffee')

@section('page-actions')
    <button id='owner-refresh' type='button' class='owner-action owner-action-light'>Muat ulang</button>
    <button id='owner-add-table' type='button' class='owner-action owner-action-light' disabled>Tambah meja</button>
    <button id='owner-edit-toggle' type='button' class='owner-action owner-action-dark' disabled>Edit denah</button>
@endsection

@push('styles')
@vite('resources/css/table-editor.css')
@endpush

@section('content')
<div class='owner-workspace {{ $statusDotsOnly ? 'status-dots-only' : '' }}'>
    <section class='owner-intro'>
        <div>
            <p class='owner-eyebrow'>Ruang &amp; operasional</p>
            <h3 id='owner-layout-name' class='mt-1 font-sans text-2xl font-bold text-espresso'>Manajemen meja</h3>
            <p id='owner-layout-updated' class='owner-updated'>Menunggu data denah...</p>
        </div>
        @if ($canViewTableHistory)
        <div class='owner-mode-tabs' aria-label='Mode tampilan'>
            <button type='button' class='owner-mode-tab is-active' data-owner-mode='monitor'>Monitoring</button>
            <button type='button' class='owner-mode-tab' data-owner-mode='history'>Riwayat</button>
        </div>
        @endif
    </section>

    <section class='owner-stat-grid' aria-live='polite'>
        <div class='owner-stat'><p class='owner-stat-label'>Total</p><p id='owner-stat-total' class='owner-stat-value'>–</p></div>
        <div class='owner-stat'><p class='owner-stat-label'><span class='owner-dot dot-available'></span>Tersedia</p><p id='owner-stat-available' class='owner-stat-value'>–</p></div>
        <div class='owner-stat'><p class='owner-stat-label'><span class='owner-dot dot-occupied'></span>Terisi</p><p id='owner-stat-occupied' class='owner-stat-value'>–</p></div>
        <div class='owner-stat'><p class='owner-stat-label'><span class='owner-dot dot-reserved'></span>Dipesan</p><p id='owner-stat-reserved' class='owner-stat-value'>–</p></div>
        <div class='owner-stat'><p class='owner-stat-label'><span class='owner-dot dot-unavailable'></span>Tidak tersedia</p><p id='owner-stat-unavailable' class='owner-stat-value'>–</p></div>
    </section>

    <section id='owner-monitor' class='owner-monitor owner-layout-grid'>
        <div class='owner-card plan-board'>
            <div class='plan-stage-header'><div class='owner-card-title'>Denah kedai <span class='plan-stage-badge'>Tampak atas</span></div><div class='plan-history-tools'><button type='button' id='plan-delete-table' class='owner-action owner-action-light plan-table-delete' title='Aktifkan Edit denah lalu pilih meja yang akan dihapus' disabled><svg viewBox='0 0 24 24' aria-hidden='true'><path d='M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13M10 10v7m4-7v7'/></svg>Hapus meja</button><button type='button' id='plan-undo' class='plan-icon-button' title='Urungkan (Ctrl+Z)' aria-label='Urungkan' disabled>↶</button><button type='button' id='plan-redo' class='plan-icon-button' title='Ulangi (Ctrl+Shift+Z)' aria-label='Ulangi' disabled>↷</button></div></div>
            <div id='owner-canvas-shell' class='owner-canvas-scroll'><div id='owner-canvas' class='owner-canvas' role='region' aria-label='Denah meja Ciks Coffee'></div></div>
            <div class='plan-board-footer'><p id='owner-board-caption' class='plan-board-hint'>Klik meja untuk melihat statusnya.</p><div class='plan-zoom'><button type='button' id='plan-zoom-out' aria-label='Perkecil'>−</button><span id='plan-zoom-value'>100%</span><button type='button' id='plan-zoom-in' aria-label='Perbesar'>+</button><button type='button' id='plan-zoom-fit'>Pas</button></div></div>
            <div id='owner-editor-bar' class='owner-editor-bar'><span id='owner-dirty-text' class='owner-dirty' aria-live='polite'>Semua perubahan tersimpan.</span><div class='flex gap-2'><button id='owner-reset-layout' type='button' class='owner-action owner-action-light'>Batalkan</button><button id='owner-save-layout' type='button' class='owner-action owner-action-dark'>Simpan denah</button></div></div>
        </div>
        <aside class='owner-card plan-inspector'><div class='owner-card-header'><div><h4 class='owner-card-title'>Properti</h4><p class='owner-card-subtitle'>Atur objek denah.</p></div></div><div id='owner-property' class='owner-property'></div></aside>
    </section>

    @if ($canViewTableHistory)
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
    @endif
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
            <div class='owner-modal-actions'><button id='owner-table-delete' type='button' class='owner-table-delete' hidden>Hapus meja</button><button type='button' class='owner-button-cancel' data-close-modal='owner-table-modal'>Batal</button><button id='owner-table-submit' type='submit' class='owner-button-save'>Simpan meja</button></div>
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
@include('components.floor-plan-elements')
<script>
(() => {
    const endpoints = {
        data: @json(route($tableRoutePrefix.'.data')),
        layout: @json(route($tableRoutePrefix.'.layout.update', ['floorLayout' => '__LAYOUT_ID__'])),
        store: @json(route($tableRoutePrefix.'.store')),
        update: @json(route($tableRoutePrefix.'.update', ['coffeeTable' => '__TABLE_ID__'])),
        destroy: @json(route($tableRoutePrefix.'.destroy', ['coffeeTable' => '__TABLE_ID__'])),
        active: @json(route($tableRoutePrefix.'.toggle-active', ['coffeeTable' => '__TABLE_ID__'])),
        status: @json(route($tableRoutePrefix.'.status.update', ['coffeeTable' => '__TABLE_ID__'])),
        history: @json($canViewTableHistory ? route($tableRoutePrefix.'.history') : null),
    };
    const csrfToken = document.querySelector('meta[name=csrf-token]').getAttribute('content');
    const labels = { available: 'Tersedia', occupied: 'Terisi', reserved: 'Dipesan', unavailable: 'Tidak tersedia' };
    const state = { data: null, selectedId: null, selectedMarkerIndex: null, editing: false, dirty: false, preview: 'desktop', loading: false, dragging: null, tableFormMode: 'create', statusTableId: null, historyPage: 1, historyMeta: null, toastTimer: null };
    let liveEpoch = 0, liveBusy = false, pendingMutations = 0;
    const elements = {
        refresh: document.getElementById('owner-refresh'), edit: document.getElementById('owner-edit-toggle'), add: document.getElementById('owner-add-table'), canvas: document.getElementById('owner-canvas'), canvasShell: document.getElementById('owner-canvas-shell'), property: document.getElementById('owner-property'), editorBar: document.getElementById('owner-editor-bar'), dirty: document.getElementById('owner-dirty-text'), saveLayout: document.getElementById('owner-save-layout'), resetLayout: document.getElementById('owner-reset-layout'), toast: document.getElementById('owner-toast'), monitor: document.getElementById('owner-monitor'), history: document.getElementById('owner-history'), historyList: document.getElementById('owner-history-list'), tableModal: document.getElementById('owner-table-modal'), statusModal: document.getElementById('owner-status-modal'), tableForm: document.getElementById('owner-table-form'), statusForm: document.getElementById('owner-status-form'),
    };

    const number = (value, fallback = 0) => Number.isFinite(Number(value)) ? Number(value) : fallback;
    const clamp = (value, min, max) => Math.min(max, Math.max(min, value));
    const round = (value) => Math.round(value * 100) / 100;
    const findTable = (id) => state.data?.tables.find((table) => Number(table.id) === Number(id)) ?? null;
    const markerTemplates = window.CiksFloorPlan.types;
    const markerTypes = Object.fromEntries(Object.entries(markerTemplates).map(([type, value]) => [type, value.label || 'Tanaman']));
    Object.assign(markerTypes, { area: 'Area', text: 'Teks', counter: 'Kasir / Bar', plant: 'Tanaman' });
    Object.assign(state, { zoom: 100, panning: false, undo: [], redo: [], snap: true, deletingTableId: null });
    const backgroundElements = () => {
        const elements = state.data?.layout?.background_config?.elements;
        return Array.isArray(elements) ? elements : [];
    };
    const findMarker = (index) => index !== null && index !== undefined && Number.isInteger(Number(index)) ? backgroundElements()[Number(index)] ?? null : null;
    const isSelectedMarker = (index) => state.selectedMarkerIndex !== null && Number(state.selectedMarkerIndex) === Number(index);
    const statusLabel = (status) => labels[status] ?? 'Tidak diketahui';


    function snapshot() {
        return JSON.stringify({ layout: state.data.layout, positions: state.data.tables.map(({ id, position_x, position_y, width, height, rotation }) => ({ id, position_x, position_y, width, height, rotation })) });
    }
    function remember(value = snapshot()) { state.undo.push(value); if (state.undo.length > 40) state.undo.shift(); state.redo = []; }
    function updateControls() {
        if (state.data && state.clean) state.dirty = snapshot() !== state.clean;
        elements.dirty.textContent = state.dirty ? 'Ada perubahan belum disimpan' : 'Semua perubahan tersimpan';
        elements.dirty.classList.toggle('is-dirty', state.dirty);
        elements.saveLayout.disabled = !state.data || !state.dirty || state.loading;
        field('plan-undo').disabled = !state.undo.length;
        field('plan-redo').disabled = !state.redo.length;
        field('plan-zoom-value').textContent = state.zoom + '%';
        const selectedTable=findTable(state.selectedId);
        field('plan-delete-table').disabled=!state.editing||!selectedTable||state.loading||state.deletingTableId!==null;
        field('plan-delete-table').title=state.editing ? (selectedTable ? 'Hapus '+selectedTable.code : 'Pilih meja pada denah atau daftar objek') : 'Aktifkan Edit denah lalu pilih meja yang akan dihapus';
        const propertyDelete=field('plan-property-delete-table');
        if(propertyDelete) propertyDelete.disabled=state.deletingTableId!==null;
        field('owner-table-delete').disabled=state.deletingTableId!==null;
    }
    function undoChange(redo = false) {
        if (!state.data) return;
        const source = redo ? state.redo : state.undo;
        const target = redo ? state.undo : state.redo;
        if (!source.length) return;
        target.push(snapshot());
        const saved = JSON.parse(source.pop());
        state.data.layout = saved.layout;
        saved.positions.forEach(position => { const table = findTable(position.id); if (table) Object.assign(table, position); });
        state.selectedMarkerIndex = null; state.selectedId = null;
        renderAll();
    }


    function changeZoom(value) {
        state.zoom = clamp(value, 50, 250);
        applyZoom();
        updateControls();
    }
    function applyZoom() {
        if (!state.data) return;
        const availableWidth = elements.canvasShell.getBoundingClientRect().width;
        if (!availableWidth) return;
        const ratio = state.data.layout.canvas_width / state.data.layout.canvas_height;
        elements.canvasShell.style.height = availableWidth / ratio + 'px';
        const width = availableWidth * state.zoom / 100;
        elements.canvas.style.width = width + 'px';
        elements.canvas.style.minWidth = width + 'px';
    }
    function setPan(value) {
        state.panning = value;
        elements.canvas.classList.toggle('is-panning', value);
    }
    function duplicateMarker() {
        const marker = findMarker(state.selectedMarkerIndex);
        if (!marker || backgroundElements().length >= 100) return;
        remember();
        backgroundElements().push({ ...marker, position_x: clamp(number(marker.position_x) + 4, 0, 100), position_y: clamp(number(marker.position_y) + 4, 0, 100) });
        state.selectedMarkerIndex = backgroundElements().length - 1;
        renderAll();
    }
    function moveMarkerLayer(front) {
        const index = state.selectedMarkerIndex;
        if (index === null || !findMarker(index)) return;
        remember();
        const [marker] = backgroundElements().splice(index, 1);
        if (front) { backgroundElements().push(marker); state.selectedMarkerIndex = backgroundElements().length - 1; }
        else { backgroundElements().unshift(marker); state.selectedMarkerIndex = 0; }
        renderAll();
    }
    function propertyField(parent, label, id, value, apply, options = {}) {
        const wrapper = document.createElement('label'); wrapper.className = 'owner-field-label' + (options.wide ? ' is-wide' : ''); wrapper.textContent = label;
        const input = document.createElement('input'); input.id = id; input.className = 'owner-input'; input.type = options.text ? 'text' : 'number'; input.value = value ?? '';
        if (options.text) input.maxLength = options.maxLength ?? 80;
        else { input.min = options.min ?? 0; input.max = options.max ?? 100; input.step = options.step ?? '.5'; }
        input.addEventListener('change', () => {
            const next = options.text ? input.value.slice(0, options.maxLength ?? 80) : round(clamp(number(input.value), number(input.min), number(input.max)));
            remember(); apply(next); input.value = next; renderCanvas(); updateControls();
        });
        wrapper.append(input); parent.append(wrapper); return input;
    }

    function formatDate(value) {
        if (!value) return 'Belum ada pembaruan.';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return 'Waktu tidak tersedia.';
        return new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short', timeZone: 'Asia/Jakarta' }).format(date);
    }

    function showToast(message, type = 'info') {
        window.CiksAlert.notify(message, type);
    }

    function setLoading(isLoading) {
        state.loading = isLoading;
        elements.refresh.disabled = isLoading;
        elements.add.disabled = isLoading || !state.data;
        if (!state.data) elements.add.classList.add('hidden');
        elements.edit.disabled = isLoading || !state.data;
        elements.saveLayout.disabled = isLoading || !state.data || !state.dirty;
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
        const item = window.CiksFloorPlan.render(marker, {
            editable: state.editing && !state.panning,
            selected: state.editing && isSelectedMarker(index),
            select: () => { if (!state.dragging) selectMarker(index); },
            drag: (event, element) => startMarkerDrag(event, index, element),
            resize: (event, element) => startResize(event, 'marker', index, element),
        });
        item.dataset.markerIndex = index;
        item.style.zIndex = index + 1;
        elements.canvas.append(item);
    }

    function renderChairs(button, table) {
        window.CiksFloorPlan.appendSeating(button, table, state.data.layout.background_config ?? {});
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
        renderChairs(button, table);
        button.append(code, name, seats);
        if (state.editing && Number(state.selectedId) === Number(table.id)) {
            const handle = document.createElement('span'); handle.className = 'plan-resize';
            handle.addEventListener('pointerdown', event => startResize(event, 'table', table.id, button)); button.append(handle);
        }
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
        window.CiksFloorPlan.applyBackground(elements.canvas,layout);
        backgroundElements().forEach(renderMarker);
        state.data.tables.forEach(renderTable);
        if (!backgroundElements().length && !state.data.tables.length && !layout.background_image) {
            const empty = document.createElement('div'); empty.className = 'plan-empty-card';
            const icon = document.createElementNS('http://www.w3.org/2000/svg', 'svg'); icon.setAttribute('viewBox','0 0 40 40');
            const path = document.createElementNS('http://www.w3.org/2000/svg', 'path'); path.setAttribute('d','M5 5h30v30H5zM5 17h17v18M22 5v12h13'); icon.append(path);
            const title = document.createElement('strong'); title.textContent = 'Mulai dari denah sederhana';
            const hint = document.createElement('p'); hint.textContent = 'Tambahkan area indoor, outdoor, atau parkir. Letakkan meja setelahnya.';
            empty.append(icon,title,hint); elements.canvas.append(empty);
        }
        applyZoom();
    }

    function renderProperty() {
        const marker=findMarker(state.selectedMarkerIndex);const table=findTable(state.selectedId);
        elements.property.replaceChildren();
        const title=document.createElement('p');title.className='owner-property-name';
        const meta=document.createElement('p');meta.className='owner-property-meta';
        const grid=document.createElement('div');grid.className='plan-field-grid';
        const actions=document.createElement('div');actions.className='owner-property-actions';
        const action=(label,handler)=>{const button=document.createElement('button');button.type='button';button.textContent=label;button.addEventListener('click',handler);actions.append(button);return button;};
        if(marker){
            const defaults=markerTemplates[marker.type];title.textContent=markerTypes[marker.type]||'Objek denah';
            meta.textContent='Geser objek atau tarik titik sudut untuk mengubah ukuran.';
            propertyField(grid,'Label','plan-label',marker.label,value=>marker.label=value,{text:true,wide:true});
            for(const [key,label,min,max] of [['position_x','Posisi X',0,100],['position_y','Posisi Y',0,100],['width','Lebar',.5,100],['height','Tinggi',.5,100],['rotation','Rotasi °',0,359.99],['font_size','Ukuran teks',8,32]]){
                propertyField(grid,label,'plan-'+key,marker[key]??defaults[key]??(key==='font_size'?12:0),value=>marker[key]=value,{min,max,step:key==='font_size'?'1':'.5'});
            }
            const fill=document.createElement('div');fill.className='is-wide';const label=document.createElement('p');label.className='owner-field-label';label.textContent='Warna objek';fill.append(label);
            const swatches=document.createElement('div');swatches.className='plan-fill-options';
            for(const [value,label] of [['none','Tanpa warna'],['latte','Latte'],['cream','Cream'],['espresso','Espresso']]){
                const button=document.createElement('button');button.type='button';button.dataset.fill=value;button.title=label;button.setAttribute('aria-label',label);
                button.className='plan-fill-option'+((marker.fill??defaults.fill??'none')===value?' is-active':'');
                button.addEventListener('click',()=>{remember();marker.fill=value;renderAll();});swatches.append(button);
            }
            fill.append(swatches);grid.append(fill);
            action('Duplikat',duplicateMarker);action('Hapus objek',event=>removeMarker(state.selectedMarkerIndex,event.currentTarget));
            action('Ke depan',()=>moveMarkerLayer(true));action('Ke belakang',()=>moveMarkerLayer(false));
            elements.property.append(title,meta,grid,actions);return;
        }
        if(table){
            title.textContent=table.code+' · '+table.name;meta.textContent=table.capacity+' kursi · '+statusLabel(table.status);
            action('Ubah detail',()=>openTableModal('edit',table));action('Ubah status',()=>openStatusModal(table)).disabled=!table.is_active;
            action(table.is_active?'Arsipkan':'Aktifkan',event=>toggleActive(table,event.currentTarget));
            action('Hapus meja',event=>deleteTable(table,event.currentTarget)).id='plan-property-delete-table';
            elements.property.append(title,meta,actions);
            if(state.editing){
                for(const [key,label,min,max] of [['position_x','Posisi X',0,99],['position_y','Posisi Y',0,99],['width','Lebar',1,100],['height','Tinggi',1,100],['rotation','Rotasi °',0,359.99]]){
                    propertyField(grid,label,'plan-table-'+key,table[key],value=>{table[key]=value;table.width=clamp(number(table.width),1,100-number(table.position_x));table.height=clamp(number(table.height),1,100-number(table.position_y));},{min,max});
                }
                elements.property.append(grid);
            }return;
        }
        title.textContent='Pengaturan denah';meta.textContent='Seluruh area kedai dalam satu kanvas.';
        if(!state.data){elements.property.append(title,meta);return;}
        propertyField(grid,'Nama denah','plan-layout-name',state.data.layout.name,value=>{if(value.trim())state.data.layout.name=value;},{text:true,wide:true,maxLength:255});
        propertyField(grid,'Lebar kanvas','plan-canvas-width',state.data.layout.canvas_width,value=>state.data.layout.canvas_width=value,{min:400,max:8192,step:1});
        propertyField(grid,'Tinggi kanvas','plan-canvas-height',state.data.layout.canvas_height,value=>state.data.layout.canvas_height=value,{min:400,max:8192,step:1});
        if(state.data.layout.background_image){
            const background=state.data.layout.background_image;
            const native=document.createElement('button');native.type='button';native.className='owner-action owner-action-light plan-native-background';native.textContent='Ukuran gambar ('+background.width+' × '+background.height+')';
            native.addEventListener('click',()=>{remember();state.data.layout.canvas_width=background.width;state.data.layout.canvas_height=background.height;renderAll();});
            native.classList.add('is-wide');grid.append(native);
        }
        const toggles=document.createElement('div');
        for(const [label,checked,apply] of [['Tampilkan grid',state.data.layout.background_config?.show_grid!==false,value=>{remember();state.data.layout.background_config={...(state.data.layout.background_config??{}),show_grid:value};renderAll();}],['Rapikan ke grid',state.snap,value=>state.snap=value]]){
            const wrapper=document.createElement('label');wrapper.className='plan-setting-toggle';wrapper.textContent=label;const input=document.createElement('input');input.type='checkbox';input.checked=checked;input.addEventListener('change',()=>apply(input.checked));wrapper.append(input);toggles.append(wrapper);
        }
        elements.property.append(title,meta,grid,toggles);
    }

    function renderTableFilter() {
        const select = document.getElementById('history-table');
        if (!select) return;
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
        setText('owner-layout-updated', 'Denah diperbarui ' + formatDate(layout.updated_at));
        renderSummary(state.data.summary);
        renderCanvas(); renderProperty(); renderTableFilter();
        document.querySelector('.owner-workspace').classList.toggle('is-editing', state.editing);
        elements.add.classList.remove('hidden');
        elements.editorBar.classList.toggle('is-visible', state.editing);
        elements.edit.className = 'owner-action ' + (state.editing ? 'owner-action-light' : 'owner-action-dark');
        elements.edit.textContent = state.editing ? 'Lihat denah' : 'Edit denah';
        elements.edit.setAttribute('aria-pressed', String(state.editing));
        updateControls();
        document.getElementById('owner-board-caption').textContent = state.editing ? 'Geser objek · Tarik sudut untuk mengubah ukuran' : 'Klik meja untuk melihat statusnya.';
    }

    async function loadData({ quiet = false } = {}) {
        if (state.loading) return;
        liveEpoch += 1;
        setLoading(true);
        try {
            const response = await fetch(endpoints.data, { cache: 'no-store', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            const payload = await response.json().catch(() => null);
            if (!response.ok || !payload?.success) throw new Error(payload?.message ?? 'Data denah tidak dapat dimuat.');
            state.data = payload.data; state.selectedId = null; state.selectedMarkerIndex = null; state.dirty = false; state.clean = snapshot(); state.undo = []; state.redo = []; renderAll();
            if (!quiet) showToast('Data denah sudah diperbarui.', 'success');
        } catch (error) {
            const message = error.message ?? 'Data denah tidak dapat dimuat.';
            setText('owner-layout-updated', message + ' Tekan Muat ulang untuk mencoba lagi.');
            if (!state.data) {
                const empty = document.createElement('p');
                empty.className = 'owner-empty';
                empty.textContent = message;
                elements.canvas.replaceChildren(empty);
            }
            if (!quiet) showToast(message, 'error');
        }
        finally { setLoading(false); }
    }

    function canRefreshLiveData() {
        return !document.hidden && !state.loading && !state.editing && !state.dirty && !state.dragging && !pendingMutations
            && elements.tableModal.classList.contains('hidden') && elements.statusModal.classList.contains('hidden');
    }

    let liveTag = null;
    async function refreshLiveData() {
        if (liveBusy || !canRefreshLiveData()) return;
        if (!state.data) { loadData({ quiet: true }); return; }
        const epoch = liveEpoch;
        liveBusy = true;
        try {
            const response = await fetch(endpoints.data, { cache: 'no-store', headers: { Accept: 'application/json', ...(liveTag ? {'If-None-Match': liveTag} : {}) }, signal: AbortSignal.timeout(8000) });
            if (response.status === 304) return;
            const payload = await response.json();
            if (!response.ok || !payload?.success || epoch !== liveEpoch || !canRefreshLiveData()) return;
            liveTag = response.headers.get('ETag');
            state.data = payload.data;
            if (!findTable(state.selectedId)) state.selectedId = null;
            state.clean = snapshot();
            renderAll();
        } catch { /* Preserve the last valid layout until the next refresh. */ }
        finally { liveBusy = false; }
    }

    function selectTable(id) {
        state.selectedId = id;
        state.selectedMarkerIndex = null;
        setPan(false); renderAll();
    }

    function selectMarker(index) {
        state.selectedId = null;
        state.selectedMarkerIndex = index;
        setPan(false); renderAll();
    }

    async function removeMarker(index, anchor) {
        const marker = findMarker(index);
        if (!marker) return;
        const approved = await window.CiksAlert.quickConfirm({
            title: 'Hapus elemen denah?',
            message: (marker.label ?? 'Elemen denah') + ' akan dihapus dari denah.',
            confirmText: 'Hapus',
            variant: 'danger',
            anchor,
        });
        if (!approved) return;
        remember();
        backgroundElements().splice(Number(index), 1);
        state.selectedMarkerIndex = null;
        state.dirty = true;
        renderAll();
    }

    function startDrag(event, table, button) {
        if (!state.editing || state.panning || event.button !== 0) return;
        event.preventDefault(); event.stopPropagation();
        state.selectedId = table.id; state.selectedMarkerIndex = null;
        state.dragging = { kind: 'table', id: table.id, button, startClientX: event.clientX, startClientY: event.clientY, startX: number(table.position_x), startY: number(table.position_y), rect: elements.canvas.getBoundingClientRect(), before: snapshot() };
        button.setPointerCapture?.(event.pointerId);
    }
    function startMarkerDrag(event, index, button) {
        if (!state.editing || state.panning || event.button !== 0) return;
        const marker = findMarker(index); if (!marker) return;
        event.preventDefault(); event.stopPropagation();
        state.selectedMarkerIndex = index; state.selectedId = null;
        state.dragging = { kind: 'marker', index, button, startClientX: event.clientX, startClientY: event.clientY, startX: number(marker.position_x, 50), startY: number(marker.position_y, 50), rect: elements.canvas.getBoundingClientRect(), before: snapshot() };
        button.setPointerCapture?.(event.pointerId);
    }
    function startResize(event, kind, id, button) {
        if (!state.editing || state.panning || event.button !== 0) return;
        event.preventDefault(); event.stopPropagation();
        const object = kind === 'marker' ? findMarker(id) : findTable(id);
        const defaults = markerTemplates[object.type] ?? {};
        state.dragging = { kind, index: id, id, resize: true, object, button, width: number(object.width ?? defaults.width), height: number(object.height ?? defaults.height), startClientX:event.clientX, startClientY:event.clientY, rect: elements.canvas.getBoundingClientRect(), before:snapshot() };
        button.setPointerCapture?.(event.pointerId);
    }
    function moveDrag(event) {
        const drag = state.dragging; if (!drag) return;
        if (drag.kind === 'pan') { elements.canvasShell.scrollLeft = drag.left - (event.clientX - drag.startClientX); elements.canvasShell.scrollTop = drag.top - (event.clientY - drag.startClientY); return; }
        const dx = ((event.clientX - drag.startClientX) / drag.rect.width) * 100;
        const dy = ((event.clientY - drag.startClientY) / drag.rect.height) * 100;
        if (Math.abs(dx) + Math.abs(dy) < .1) return;
        const object = drag.kind === 'marker' ? findMarker(drag.index) : findTable(drag.id);
        if (!object) return;
        const snap = value => state.snap ? Math.round(value) : round(value);
        if (drag.resize) {
            const radians = number(object.rotation) * Math.PI / 180;
            const localX = Math.cos(radians) * dx + Math.sin(radians) * dy * drag.rect.height / drag.rect.width;
            const localY = -Math.sin(radians) * dx * drag.rect.width / drag.rect.height + Math.cos(radians) * dy;
            object.width = round(clamp(snap(drag.width + localX), 1, drag.kind === 'table' ? 100 - object.position_x : 100));
            object.height = round(clamp(snap(drag.height + localY), drag.kind === 'table' ? 1 : .5, drag.kind === 'table' ? 100 - object.position_y : 100));
            drag.button.style.width = object.width + '%'; drag.button.style.height = object.height + '%';
        } else {
            object.position_x = round(clamp(snap(drag.startX + dx), 0, drag.kind === 'table' ? 100 - number(object.width) : 100));
            object.position_y = round(clamp(snap(drag.startY + dy), 0, drag.kind === 'table' ? 100 - number(object.height) : 100));
            drag.button.style.left = object.position_x + '%'; drag.button.style.top = object.position_y + '%';
        }
        drag.moved = true; updateControls();
    }
    function endDrag() {
        const drag = state.dragging; if (!drag) return;
        state.dragging = null;
        if (drag.kind === 'pan') return;
        if (drag.moved) remember(drag.before);
        renderAll();
    }

    function openModal(modal) { modal.classList.remove('hidden'); modal.setAttribute('aria-hidden', 'false'); }
    function closeModal(modal) { modal.classList.add('hidden'); modal.setAttribute('aria-hidden', 'true'); }
    function field(id) { return document.getElementById(id); }

    function openTableModal(mode, table = null) {
        state.tableFormMode = mode;
        field('owner-table-modal-title').textContent = mode === 'create' ? 'Tambah meja baru' : 'Ubah detail meja';
        field('owner-table-modal-subtitle').textContent = mode === 'create' ? 'Data posisi dapat disesuaikan kembali melalui editor layout.' : 'Perubahan detail memakai versi data terbaru.';
        field('owner-table-submit').textContent = mode === 'create' ? 'Tambah meja' : 'Simpan detail';
        field('owner-table-delete').hidden=mode!=='edit';
        elements.tableForm.dataset.tableId = table?.id ?? '';
        field('table-code').value = table?.code ?? '';
        field('table-name').value = table?.name ?? '';
        field('table-capacity').value = table?.capacity ?? 2;
        field('table-shape').value = table?.shape ?? 'round';
        field('table-x').value = table?.position_x ?? 43;
        field('table-y').value = table?.position_y ?? 43;
        field('table-width').value = table?.width ?? 6.67;
        field('table-height').value = table?.height ?? round(6.67 * number(state.data?.layout.canvas_width,1200) / number(state.data?.layout.canvas_height,800));
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
        const pending = state.dirty;
        const index = state.data.tables.findIndex((table) => Number(table.id) === Number(updatedTable.id));
        if (pending && index >= 0) {
            const previous = state.data.tables[index];
            const saved = JSON.parse(state.clean).positions.find(table => Number(table.id) === Number(previous.id));
            for (const key of ['position_x', 'position_y', 'width', 'height', 'rotation']) {
                if (saved && Number(previous[key]) !== Number(saved[key])) updatedTable[key] = previous[key];
            }
        }
        if (index >= 0) state.data.tables.splice(index, 1, updatedTable);
        else state.data.tables.push(updatedTable);
        if (!pending) state.clean = snapshot();
        state.undo = []; state.redo = [];
        recalculateSummary();
        renderAll();
    }

    async function requestJson(url, method = 'GET', body = null) {
        const mutating = method !== 'GET';
        if (mutating) { pendingMutations += 1; liveEpoch += 1; }
        try {
            const options = { method, headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } };
            if (body !== null) {
                options.headers['Content-Type'] = 'application/json';
                options.headers['X-CSRF-TOKEN'] = csrfToken;
                options.body = JSON.stringify(body);
            }
            const response = await fetch(url, options);
            const payload = await response.json().catch(() => null);
            return { response, payload };
        } finally {
            if (mutating) { pendingMutations -= 1; liveEpoch += 1; }
        }
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

    async function toggleActive(table, anchor) {
        const action = table.is_active ? 'arsipkan' : 'aktifkan';
        const approved = await window.CiksAlert.quickConfirm({
            title: 'Konfirmasi status meja',
            message: 'Yakin ingin ' + action + ' ' + table.code + '? Riwayat status tetap disimpan.',
            confirmText: action === 'arsipkan' ? 'Arsipkan' : 'Aktifkan',
            anchor,
        });
        if (!approved) return;
        try {
            const result = await requestJson(endpoints.active.replace('__TABLE_ID__', String(table.id)), 'PATCH', { is_active: !table.is_active, version: table.version });
            if (result.response.status === 409) { conflictMessage(result.payload); return; }
            if (!result.response.ok || !result.payload?.success) throw new Error(result.payload?.message ?? 'Status arsip meja tidak dapat diubah.');
            replaceTable(result.payload.data); showToast(result.payload.message ?? 'Status meja berhasil diperbarui.', 'success');
        } catch (error) { showToast(error.message ?? 'Status arsip meja tidak dapat diubah.', 'error'); }
    }

    async function deleteTable(table, anchor) {
        if(!table||state.deletingTableId!==null) return;
        const message = 'Meja ' + table.code + ' akan dihapus permanen.';
        const approved = await window.CiksAlert.quickConfirm({
            title: 'Hapus meja?',
            message,
            confirmText: 'Hapus meja',
            variant: 'danger',
            anchor,
        });
        if (!approved) return;
        state.deletingTableId=table.id; updateControls();
        try {
            const result = await requestJson(endpoints.destroy.replace('__TABLE_ID__', String(table.id)), 'DELETE', { version: table.version });
            if (result.response.status === 409) { conflictMessage(result.payload); return; }
            if (!result.response.ok || !result.payload?.success) throw new Error(result.payload?.message ?? 'Meja tidak dapat dihapus.');
            state.data.tables = state.data.tables.filter((item) => Number(item.id) !== Number(table.id));
            if(state.clean){const baseline=JSON.parse(state.clean);baseline.positions=baseline.positions.filter(item=>Number(item.id)!==Number(table.id));state.clean=JSON.stringify(baseline);}
            if(Number(elements.tableForm.dataset.tableId)===Number(table.id)) closeModal(elements.tableModal);
            state.selectedId = null;
            state.undo = []; state.redo = [];
            recalculateSummary();
            renderAll();
            showToast(result.payload.message ?? 'Meja berhasil dihapus.', 'success');
        } catch (error) { showToast(error.message ?? 'Meja tidak dapat dihapus.', 'error'); }
        finally { state.deletingTableId=null; updateControls(); }
    }

    async function saveLayout() {
        if (!state.data || !state.dirty) { showToast('Belum ada perubahan posisi yang perlu disimpan.', 'info'); return; }
        elements.saveLayout.disabled = true; elements.saveLayout.textContent = 'Menyimpan...';
        try {
            const config = state.data.layout.background_config ?? {};
            const payload = {
                name: state.data.layout.name,
                canvas_width: state.data.layout.canvas_width,
                canvas_height: state.data.layout.canvas_height,
                background_config: {
                    ...config,
                    elements: backgroundElements().map(marker => ({ type: marker.type, label: marker.label ?? '', position_x: number(marker.position_x, 50), position_y: number(marker.position_y, 50), width: number(marker.width ?? markerTemplates[marker.type]?.width, 15), height: number(marker.height ?? markerTemplates[marker.type]?.height, 10), rotation: number(marker.rotation), fill: marker.fill ?? markerTemplates[marker.type]?.fill ?? 'none', font_size: Math.round(number(marker.font_size, 12)) })),
                },
                tables: state.data.tables.map((table) => ({ id: table.id, position_x: table.position_x, position_y: table.position_y, width: table.width, height: table.height, rotation: table.rotation, version: table.version })),
            };
            const result = await requestJson(endpoints.layout.replace('__LAYOUT_ID__', String(state.data.layout.id)), 'PUT', payload);
            if (result.response.status === 409) { conflictMessage(result.payload); return; }
            if (!result.response.ok || !result.payload?.success) throw new Error(result.payload?.message ?? 'Layout tidak dapat disimpan.');
            state.data = result.payload.data; state.clean = snapshot(); state.undo = []; state.redo = []; state.dirty = false; renderAll(); showToast(result.payload.message ?? 'Denah meja berhasil disimpan.', 'success');
        } catch (error) { showToast(error.message ?? 'Layout tidak dapat disimpan.', 'error'); }
        finally { updateControls(); elements.saveLayout.textContent = 'Simpan denah'; }
    }

    async function toggleEditMode(anchor) {
        if (state.editing && state.dirty) {
            const approved = await window.CiksAlert.quickConfirm({
                title: 'Batalkan perubahan?',
                message: 'Perubahan posisi yang belum disimpan akan dibatalkan.',
                confirmText: 'Batalkan perubahan',
                anchor,
            });
            if (!approved) return;
            state.editing = false; state.selectedMarkerIndex = null; loadData({ quiet: true }); return;
        }
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
        if (!endpoints.history) return;
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
        if (mode === 'history' && !endpoints.history) return;
        document.querySelectorAll('[data-owner-mode]').forEach((button) => button.classList.toggle('is-active', button.dataset.ownerMode === mode));
        elements.monitor.classList.toggle('hidden', mode !== 'monitor');
        elements.history?.classList.toggle('hidden', mode !== 'history');
        if (mode === 'history') loadHistory(1);
    }

    elements.refresh.addEventListener('click', async event => {
        if (state.dirty && !await window.CiksAlert.quickConfirm({ title: 'Muat ulang denah?', message: 'Perubahan yang belum disimpan akan dibatalkan.', confirmText: 'Muat ulang', anchor: event.currentTarget })) return;
        loadData();
    });
    field('plan-delete-table').addEventListener('click', event=>deleteTable(findTable(state.selectedId),event.currentTarget));
    field('owner-table-delete').addEventListener('click', event=>deleteTable(findTable(elements.tableForm.dataset.tableId),event.currentTarget));
    field('plan-undo').addEventListener('click', () => undoChange());
    field('plan-redo').addEventListener('click', () => undoChange(true));
    field('plan-zoom-in').addEventListener('click', () => changeZoom(state.zoom + 25));
    field('plan-zoom-out').addEventListener('click', () => changeZoom(state.zoom - 25));
    field('plan-zoom-fit').addEventListener('click', () => { changeZoom(100); elements.canvasShell.scrollTop = 0; elements.canvasShell.scrollLeft = 0; });
    elements.canvas.addEventListener('pointerdown', event => {
        if (state.panning && event.button === 0) {
            event.preventDefault(); state.dragging = { kind:'pan', left:elements.canvasShell.scrollLeft, top:elements.canvasShell.scrollTop, startClientX:event.clientX, startClientY:event.clientY };
            elements.canvas.setPointerCapture(event.pointerId); return;
        }
        if (event.target === elements.canvas) { state.selectedId = null; state.selectedMarkerIndex = null; renderAll(); }
    });
    document.addEventListener('keydown', event => {
        if (!state.data || !elements.tableModal.classList.contains('hidden') || !elements.statusModal.classList.contains('hidden')) return;
        const key = event.key.toLowerCase();
        if ((event.ctrlKey || event.metaKey) && key === 's') { event.preventDefault(); event.target.blur?.(); saveLayout(); return; }
        if (event.target.closest('input, textarea, select')) return;
        if ((event.ctrlKey || event.metaKey) && key === 'z') { event.preventDefault(); undoChange(event.shiftKey); }
        else if ((event.ctrlKey || event.metaKey) && key === 'y') { event.preventDefault(); undoChange(true); }
        else if ((event.ctrlKey || event.metaKey) && key === 'd' && state.selectedMarkerIndex !== null) { event.preventDefault(); duplicateMarker(); }
        else if (key === 'delete' && state.editing && state.selectedMarkerIndex !== null) { event.preventDefault(); removeMarker(state.selectedMarkerIndex); }
        else if (key === 'v') setPan(false);
        else if (key === 'h') setPan(true);
    });
    window.addEventListener('beforeunload', event => { if (state.dirty) { event.preventDefault(); event.returnValue = ''; } });
    new ResizeObserver(() => applyZoom()).observe(elements.canvasShell);
    elements.add.addEventListener('click', () => { if (state.data) openTableModal('create'); else showToast('Tunggu denah selesai dimuat.', 'info'); });
    elements.edit.addEventListener('click', (event) => toggleEditMode(event.currentTarget));
    elements.saveLayout.addEventListener('click', saveLayout);
    elements.resetLayout.addEventListener('click', async (event) => {
        if (!state.dirty) { loadData({ quiet: true }); return; }
        const approved = await window.CiksAlert.quickConfirm({
            title: 'Muat ulang denah?',
            message: 'Seluruh perubahan posisi yang belum disimpan akan dibatalkan.',
            confirmText: 'Batalkan perubahan',
            anchor: event.currentTarget,
        });
        if (approved) loadData({ quiet: true });
    });
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
    document.getElementById('owner-history-refresh')?.addEventListener('click', () => loadHistory(1));
    document.getElementById('owner-history-clear')?.addEventListener('click', () => { field('history-table').value = ''; field('history-status').value = ''; field('history-from').value = ''; field('history-to').value = ''; loadHistory(1); });
    document.getElementById('history-previous')?.addEventListener('click', () => { if (state.historyMeta?.current_page > 1) loadHistory(state.historyMeta.current_page - 1); });
    document.getElementById('history-next')?.addEventListener('click', () => { if (state.historyMeta?.current_page < state.historyMeta?.last_page) loadHistory(state.historyMeta.current_page + 1); });
    loadData({ quiet: true });
    setInterval(refreshLiveData, 8000);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) refreshLiveData(); });
    window.addEventListener('focus', refreshLiveData);
})();
</script>
@endpush
