const queue = document.querySelector('[data-order-queue-revision]');

if (queue) {
    let checking = false;
    let changed = false;
    const busy = () => document.querySelector('.ciks-alert-overlay.is-open, #proof-modal:not(.hidden), #receipt-modal:not(.hidden)')
        || ['INPUT', 'SELECT', 'TEXTAREA'].includes(document.activeElement?.tagName);
    const check = async () => {
        if (checking || document.hidden) return;
        if (changed && !busy()) { window.location.reload(); return; }
        checking = true;
        try {
            const response = await fetch(queue.dataset.revisionUrl, {headers: {'Accept': 'application/json'}, cache: 'no-store'});
            if (!response.ok) return;
            const data = await response.json();
            changed = Boolean(data.revision && data.revision !== queue.dataset.orderQueueRevision);
            if (changed && !busy()) window.location.reload();
        } catch {
            // Keep the visible queue usable; the next poll recovers connectivity.
        } finally { checking = false; }
    };
    window.addEventListener('ciks:mobile-order-received', check);
    document.addEventListener('visibilitychange', check);
    window.setInterval(check, 15000);
}
