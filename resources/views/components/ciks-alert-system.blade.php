<div id="ciks-alert-system">
    <div id="ciks-toast-stack" class="ciks-toast-stack" aria-live="polite" aria-atomic="true"></div>

    <div id="ciks-alert-sheet" class="ciks-alert-overlay" aria-hidden="true">
        <button type="button" class="ciks-alert-backdrop" data-ciks-sheet-cancel aria-label="Tutup konfirmasi"></button>
        <section class="ciks-alert-sheet" role="alertdialog" aria-modal="true" aria-labelledby="ciks-sheet-title" aria-describedby="ciks-sheet-message">
            <div class="ciks-sheet-handle"></div>
            <span id="ciks-sheet-icon" class="ciks-sheet-icon" aria-hidden="true">!</span>
            <p class="ciks-alert-kicker">Ciks Coffee</p>
            <h2 id="ciks-sheet-title">Konfirmasi tindakan</h2>
            <p id="ciks-sheet-message"></p>
            <div class="ciks-alert-actions">
                <button type="button" class="ciks-alert-button secondary" data-ciks-sheet-cancel>Batal</button>
                <button type="button" id="ciks-sheet-confirm" class="ciks-alert-button primary">Ya, Lanjutkan</button>
            </div>
        </section>
    </div>

    <div id="ciks-quick-confirm" class="ciks-quick-confirm" aria-hidden="true" role="dialog" aria-labelledby="ciks-quick-title">
        <p id="ciks-quick-title" class="ciks-quick-title">Lanjutkan tindakan?</p>
        <p id="ciks-quick-message" class="ciks-quick-message"></p>
        <div class="ciks-quick-actions">
            <button type="button" class="ciks-quick-button cancel" data-ciks-quick-cancel>Batal</button>
            <button type="button" id="ciks-quick-confirm-button" class="ciks-quick-button confirm">Lanjutkan</button>
        </div>
    </div>

    <div id="ciks-receipt-confirm" class="ciks-alert-overlay" aria-hidden="true">
        <button type="button" class="ciks-alert-backdrop" data-ciks-receipt-cancel aria-label="Tutup konfirmasi pembayaran"></button>
        <section class="ciks-receipt-confirm" role="alertdialog" aria-modal="true" aria-labelledby="ciks-receipt-title">
            <div class="ciks-receipt-top"></div>
            <div class="ciks-receipt-content">
                <p class="ciks-alert-kicker">Ciks Coffee · POS</p>
                <h2 id="ciks-receipt-title">Konfirmasi pembayaran</h2>
                <p class="ciks-receipt-caption">Pastikan detail pesanan sudah benar sebelum dicetak.</p>
                <dl class="ciks-receipt-details">
                    <div><dt>Pelanggan</dt><dd id="ciks-receipt-customer">-</dd></div>
                    <div><dt>Pembayaran</dt><dd id="ciks-receipt-method">-</dd></div>
                    <div class="ciks-receipt-total"><dt>Total</dt><dd id="ciks-receipt-total">Rp0</dd></div>
                </dl>
                <div class="ciks-alert-actions stacked-mobile">
                    <button type="button" class="ciks-alert-button secondary" data-ciks-receipt-cancel>Periksa Lagi</button>
                    <button type="button" id="ciks-receipt-confirm-button" class="ciks-alert-button primary">Proses Pembayaran</button>
                </div>
            </div>
            <div class="ciks-receipt-bottom"></div>
        </section>
    </div>
</div>

<style>
    :root { --ciks-espresso:#2d1b12; --ciks-coffee:#7b4c34; --ciks-cream:#fffaf4; --ciks-caramel:#d89556; --ciks-danger:#b84c45; --ciks-success:#337c61; }
    .ciks-toast-stack { position:fixed; z-index:1200; right:1.25rem; bottom:1.25rem; display:grid; gap:.7rem; width:min(24rem,calc(100vw - 2rem)); pointer-events:none; }
    .ciks-toast { display:grid; grid-template-columns:auto 1fr auto; align-items:start; gap:.7rem; padding:.85rem .9rem; border:1px solid rgba(92,56,37,.13); border-left:4px solid var(--ciks-coffee); border-radius:1rem; color:var(--ciks-espresso); background:rgba(255,250,244,.98); box-shadow:0 18px 44px rgba(45,27,18,.2); transform:translateY(1rem); opacity:0; animation:ciks-toast-in .28s ease forwards; pointer-events:auto; }
    .ciks-toast.success { border-left-color:var(--ciks-success); } .ciks-toast.error { border-left-color:var(--ciks-danger); } .ciks-toast.warning { border-left-color:#c98021; }
    .ciks-toast-icon { display:grid; place-items:center; width:1.8rem; height:1.8rem; border-radius:50%; color:#fff; background:var(--ciks-coffee); font-size:.82rem; font-weight:800; } .ciks-toast.success .ciks-toast-icon { background:var(--ciks-success); } .ciks-toast.error .ciks-toast-icon { background:var(--ciks-danger); } .ciks-toast.warning .ciks-toast-icon { background:#c98021; }
    .ciks-toast-title { margin:0 0 .12rem; font-size:.83rem; font-weight:800; letter-spacing:.01em; } .ciks-toast-message { margin:0; color:#6f5547; font-size:.79rem; line-height:1.45; } .ciks-toast-close { appearance:none; border:0; background:transparent; color:#8d7567; cursor:pointer; font-size:1.15rem; line-height:1; padding:.05rem; }
    .ciks-alert-overlay { position:fixed; z-index:1250; inset:0; display:grid; place-items:center; padding:1rem; visibility:hidden; opacity:0; transition:opacity .2s ease,visibility .2s ease; } .ciks-alert-overlay.is-open { visibility:visible; opacity:1; } .ciks-alert-backdrop { position:absolute; inset:0; appearance:none; border:0; background:rgba(33,18,11,.56); backdrop-filter:blur(3px); cursor:default; }
    .ciks-alert-sheet,.ciks-receipt-confirm { position:relative; z-index:1; width:min(29rem,100%); border:1px solid rgba(120,72,48,.15); border-radius:1.35rem; color:var(--ciks-espresso); background:var(--ciks-cream); box-shadow:0 28px 80px rgba(26,14,9,.34); transform:translateY(1rem) scale(.98); transition:transform .22s ease; overflow:hidden; } .is-open .ciks-alert-sheet,.is-open .ciks-receipt-confirm { transform:translateY(0) scale(1); }
    .ciks-alert-sheet { padding:1.5rem; text-align:center; } .ciks-sheet-handle { display:none; } .ciks-sheet-icon { display:grid; place-items:center; width:2.9rem; height:2.9rem; margin:0 auto .8rem; border-radius:50%; color:#fff; background:var(--ciks-coffee); font-size:1.15rem; font-weight:900; } .ciks-sheet-icon.danger { background:var(--ciks-danger); }
    .ciks-alert-kicker { margin:0 0 .3rem; color:var(--ciks-caramel); font-size:.69rem; font-weight:800; letter-spacing:.13em; text-transform:uppercase; } .ciks-alert-sheet h2,.ciks-receipt-confirm h2 { margin:0; font-size:1.18rem; font-weight:800; } .ciks-alert-sheet > p:not(.ciks-alert-kicker) { margin:.55rem auto 1.25rem; max-width:24rem; color:#6f5547; font-size:.85rem; line-height:1.55; }
    .ciks-alert-actions { display:flex; gap:.65rem; justify-content:center; } .ciks-alert-button { min-height:2.6rem; border-radius:.72rem; padding:.55rem .9rem; border:1px solid transparent; cursor:pointer; font-size:.8rem; font-weight:750; transition:transform .16s ease,filter .16s ease; } .ciks-alert-button:hover,.ciks-quick-button:hover { transform:translateY(-1px); filter:brightness(.97); } .ciks-alert-button.primary { color:#fff; background:var(--ciks-coffee); } .ciks-alert-button.primary.danger { background:var(--ciks-danger); } .ciks-alert-button.secondary { color:#64483a; border-color:#e4d7cd; background:#fff; }
    .ciks-quick-confirm { position:fixed; z-index:1240; display:none; width:min(17rem,calc(100vw - 2rem)); padding:.82rem; border:1px solid rgba(120,72,48,.16); border-radius:.9rem; color:var(--ciks-espresso); background:#fffaf4; box-shadow:0 18px 45px rgba(45,27,18,.25); } .ciks-quick-confirm.is-open { display:block; animation:ciks-quick-in .18s ease; } .ciks-quick-title { margin:0; font-size:.8rem; font-weight:800; } .ciks-quick-message { margin:.3rem 0 .7rem; color:#765d4f; font-size:.73rem; line-height:1.4; } .ciks-quick-actions { display:flex; gap:.45rem; justify-content:flex-end; } .ciks-quick-button { border:0; border-radius:.55rem; padding:.42rem .6rem; cursor:pointer; font-size:.72rem; font-weight:750; } .ciks-quick-button.cancel { color:#715446; background:#f2e8df; } .ciks-quick-button.confirm { color:#fff; background:var(--ciks-coffee); } .ciks-quick-button.confirm.danger { background:var(--ciks-danger); }
    .ciks-receipt-content { padding:1.5rem; } .ciks-receipt-caption { margin:.45rem 0 1rem; color:#786054; font-size:.8rem; line-height:1.45; } .ciks-receipt-details { margin:0 0 1.1rem; border-top:1px dashed #d7c1b1; border-bottom:1px dashed #d7c1b1; padding:.65rem 0; } .ciks-receipt-details div { display:flex; justify-content:space-between; gap:1rem; padding:.35rem 0; font-size:.78rem; } .ciks-receipt-details dt { color:#80675a; } .ciks-receipt-details dd { margin:0; max-width:65%; text-align:right; font-weight:750; } .ciks-receipt-details .ciks-receipt-total { margin-top:.35rem; padding-top:.65rem; border-top:1px dashed #d7c1b1; font-size:.95rem; } .ciks-receipt-details .ciks-receipt-total dd { color:var(--ciks-coffee); font-weight:900; } .ciks-receipt-top,.ciks-receipt-bottom { height:.45rem; background:repeating-radial-gradient(circle at 0 0, transparent 0 .26rem, var(--ciks-cream) .28rem .52rem); opacity:.8; }
    @keyframes ciks-toast-in { to { transform:translateY(0); opacity:1; } } @keyframes ciks-quick-in { from { opacity:0; transform:translateY(-.35rem); } to { opacity:1; transform:translateY(0); } }
    @media (max-width:640px) { .ciks-toast-stack { right:1rem; bottom:1rem; width:calc(100vw - 2rem); } .ciks-alert-overlay { align-items:end; padding:0; } .ciks-alert-sheet,.ciks-receipt-confirm { width:100%; border-radius:1.35rem 1.35rem 0 0; } .ciks-alert-sheet { padding:1rem 1rem calc(1.35rem + env(safe-area-inset-bottom)); } .ciks-sheet-handle { display:block; width:2.5rem; height:.24rem; margin:0 auto 1rem; border-radius:999px; background:#d8c5b8; } .ciks-receipt-content { padding:1.15rem 1rem calc(1.35rem + env(safe-area-inset-bottom)); } .ciks-alert-actions.stacked-mobile { flex-direction:column-reverse; } .ciks-alert-actions.stacked-mobile .ciks-alert-button { width:100%; } }
    @media (prefers-reduced-motion:reduce) { .ciks-toast,.ciks-alert-overlay,.ciks-alert-sheet,.ciks-receipt-confirm { animation:none!important; transition:none!important; } }
</style>

<script>
    (() => {
        if (window.CiksAlert) return;

        const byId = (id) => document.getElementById(id);
        const nodes = {
            toastStack: byId('ciks-toast-stack'), sheet: byId('ciks-alert-sheet'), sheetTitle: byId('ciks-sheet-title'), sheetMessage: byId('ciks-sheet-message'), sheetIcon: byId('ciks-sheet-icon'), sheetConfirm: byId('ciks-sheet-confirm'), quick: byId('ciks-quick-confirm'), quickTitle: byId('ciks-quick-title'), quickMessage: byId('ciks-quick-message'), quickConfirm: byId('ciks-quick-confirm-button'), receipt: byId('ciks-receipt-confirm'), receiptCustomer: byId('ciks-receipt-customer'), receiptMethod: byId('ciks-receipt-method'), receiptTotal: byId('ciks-receipt-total'), receiptConfirm: byId('ciks-receipt-confirm-button')
        };
        let pendingSheet = null, pendingQuick = null, pendingReceipt = null;
        const open = (element) => { element.classList.add('is-open'); element.setAttribute('aria-hidden', 'false'); };
        const close = (element) => { element.classList.remove('is-open'); element.setAttribute('aria-hidden', 'true'); };
        const iconFor = (type) => ({ success: '✓', error: '!', warning: '!', info: 'i' }[type] || 'i');
        const defaultTitle = (type) => ({ success: 'Berhasil diperbarui', error: 'Terjadi kendala', warning: 'Periksa kembali', info: 'Informasi' }[type] || 'Informasi');

        function notify(message, type = 'info', title = null) {
            const toast = document.createElement('article'); toast.className = 'ciks-toast ' + type;
            const icon = document.createElement('span'); icon.className = 'ciks-toast-icon'; icon.textContent = iconFor(type);
            const copy = document.createElement('div'); const heading = document.createElement('p'); heading.className = 'ciks-toast-title'; heading.textContent = title || defaultTitle(type); const body = document.createElement('p'); body.className = 'ciks-toast-message'; body.textContent = message; copy.append(heading, body);
            const dismiss = document.createElement('button'); dismiss.type = 'button'; dismiss.className = 'ciks-toast-close'; dismiss.setAttribute('aria-label', 'Tutup notifikasi'); dismiss.textContent = '×';
            const remove = () => { toast.remove(); }; dismiss.addEventListener('click', remove); toast.append(icon, copy, dismiss); nodes.toastStack.append(toast); window.setTimeout(remove, 5000);
        }

        function confirm(options = {}) {
            if (pendingSheet) pendingSheet(false);
            nodes.sheetTitle.textContent = options.title || 'Lanjutkan tindakan ini?'; nodes.sheetMessage.textContent = options.message || 'Tindakan ini akan segera diproses.'; nodes.sheetConfirm.textContent = options.confirmText || 'Ya, Lanjutkan'; nodes.sheetConfirm.classList.toggle('danger', options.variant === 'danger'); nodes.sheetIcon.textContent = options.variant === 'danger' ? '!' : '✓'; nodes.sheetIcon.classList.toggle('danger', options.variant === 'danger'); open(nodes.sheet); window.setTimeout(() => nodes.sheetConfirm.focus(), 30);
            return new Promise((resolve) => { pendingSheet = resolve; });
        }
        function finishSheet(value) { if (!pendingSheet) return; const resolve = pendingSheet; pendingSheet = null; close(nodes.sheet); resolve(value); }

        function quickConfirm(options = {}) {
            if (pendingQuick) pendingQuick(false);
            nodes.quickTitle.textContent = options.title || 'Lanjutkan tindakan?'; nodes.quickMessage.textContent = options.message || ''; nodes.quickConfirm.textContent = options.confirmText || 'Lanjutkan'; nodes.quickConfirm.classList.toggle('danger', options.variant === 'danger');
            const box = options.anchor?.getBoundingClientRect?.(); const width = Math.min(272, window.innerWidth - 32); const left = Math.max(16, Math.min((box ? box.right - width : window.innerWidth - width - 16), window.innerWidth - width - 16)); const top = box ? Math.min(box.bottom + 8, window.innerHeight - 145) : Math.max(16, (window.innerHeight - 130) / 2); nodes.quick.style.width = width + 'px'; nodes.quick.style.left = left + 'px'; nodes.quick.style.top = top + 'px'; open(nodes.quick); window.setTimeout(() => nodes.quickConfirm.focus(), 20);
            return new Promise((resolve) => { pendingQuick = resolve; });
        }
        function finishQuick(value) { if (!pendingQuick) return; const resolve = pendingQuick; pendingQuick = null; close(nodes.quick); resolve(value); }

        function receiptConfirm(options = {}) {
            if (pendingReceipt) pendingReceipt(false);
            nodes.receiptCustomer.textContent = options.customer || '-'; nodes.receiptMethod.textContent = options.paymentMethod || '-'; nodes.receiptTotal.textContent = options.total || 'Rp0'; nodes.receiptConfirm.textContent = options.confirmText || 'Proses Pembayaran'; open(nodes.receipt); window.setTimeout(() => nodes.receiptConfirm.focus(), 30);
            return new Promise((resolve) => { pendingReceipt = resolve; });
        }
        function finishReceipt(value) { if (!pendingReceipt) return; const resolve = pendingReceipt; pendingReceipt = null; close(nodes.receipt); resolve(value); }

        document.querySelectorAll('[data-ciks-sheet-cancel]').forEach((button) => button.addEventListener('click', () => finishSheet(false)));
        nodes.sheetConfirm.addEventListener('click', () => finishSheet(true));
        document.querySelectorAll('[data-ciks-quick-cancel]').forEach((button) => button.addEventListener('click', () => finishQuick(false)));
        nodes.quickConfirm.addEventListener('click', () => finishQuick(true));
        document.querySelectorAll('[data-ciks-receipt-cancel]').forEach((button) => button.addEventListener('click', () => finishReceipt(false)));
        nodes.receiptConfirm.addEventListener('click', () => finishReceipt(true));
        document.addEventListener('keydown', (event) => { if (event.key !== 'Escape') return; finishSheet(false); finishQuick(false); finishReceipt(false); });
        document.addEventListener('pointerdown', (event) => { if (pendingQuick && !nodes.quick.contains(event.target)) finishQuick(false); });
        document.addEventListener('submit', async (event) => {
            const form = event.target.closest('form[data-ciks-confirm]');
            if (!form || form.dataset.ciksSubmitting === 'true') return;
            event.preventDefault();
            const approved = await confirm({ title: form.dataset.ciksConfirmTitle, message: form.dataset.ciksConfirmMessage, confirmText: form.dataset.ciksConfirmAction, variant: form.dataset.ciksConfirmVariant });
            if (!approved) return;
            form.dataset.ciksSubmitting = 'true';
            HTMLFormElement.prototype.submit.call(form);
        });
        document.querySelectorAll('#flash-success, #flash-error').forEach((flash) => {
            const message = flash.textContent.trim();
            if (message) notify(message, flash.id === 'flash-error' ? 'error' : 'success');
            flash.remove();
        });
        window.CiksAlert = Object.freeze({ notify, confirm, quickConfirm, receiptConfirm });
    })();
</script>