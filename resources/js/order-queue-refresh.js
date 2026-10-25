import { createOrderQueueSync } from './order-queue-sync.js';

const queue = document.querySelector('[data-order-queue-revision]');

if (queue) {
    const status = document.getElementById('order-queue-sync-status');
    const message = (text, failed = false) => {
        if (!status) return;
        status.textContent = text;
        status.className = failed ? 'mb-4 text-xs text-amber-700' : 'sr-only';
    };
    const copyAttributes = (target, source) => {
        for (const attribute of [...target.attributes]) {
            if (!source.hasAttribute(attribute.name)) target.removeAttribute(attribute.name);
        }
        for (const attribute of source.attributes) target.setAttribute(attribute.name, attribute.value);
    };
    const applySnapshot = data => {
        const next = new DOMParser().parseFromString(data.html, 'text/html').querySelector('[data-order-queue-revision]');
        const selectors = ['[data-queue-filters]', '[data-queue-list]', '[data-queue-pagination]'];
        if (!next || selectors.some(selector => !next.querySelector(selector) || !queue.querySelector(selector))) {
            throw new Error('Antrean tidak lengkap.');
        }

        // Keep unchanged cards and controls mounted, including their focus and click handlers.
        const list = queue.querySelector('[data-queue-list]');
        const current = new Map([...list.children].map(node => [node.dataset.orderId || 'empty', node]));
        const candidates = [...next.querySelector('[data-queue-list]').children];
        const keys = new Set(candidates.map(node => node.dataset.orderId || 'empty'));
        for (const [key, node] of current) if (!keys.has(key)) node.remove();
        const retained = new Set();
        candidates.forEach((candidate, index) => {
            const old = current.get(candidate.dataset.orderId || 'empty');
            const node = old?.isEqualNode(candidate) ? old : document.importNode(candidate, true);
            if (old && old !== node) old.replaceWith(node);
            retained.add(node);
            if (list.children[index] !== node) list.insertBefore(node, list.children[index] || null);
        });
        for (const node of [...list.children]) if (!retained.has(node)) node.remove();

        const filters = queue.querySelector('[data-queue-filters]');
        [...next.querySelector('[data-queue-filters]').children].forEach((candidate, index) => {
            const old = filters.children[index];
            if (old?.tagName === candidate.tagName && old.getAttribute('href') === candidate.getAttribute('href')) {
                copyAttributes(old, candidate);
                if (!old.isEqualNode(candidate)) old.replaceChildren(...[...candidate.childNodes].map(node => document.importNode(node, true)));
            } else if (old) old.replaceWith(document.importNode(candidate, true));
            else filters.append(document.importNode(candidate, true));
        });
        const pagination = queue.querySelector('[data-queue-pagination]');
        const nextPagination = next.querySelector('[data-queue-pagination]');
        if (!pagination.isEqualNode(nextPagination)) pagination.replaceChildren(...[...nextPagination.childNodes].map(node => document.importNode(node, true)));

        queue.dataset.orderQueueRevision = data.revision;
        queue.dataset.snapshotUrl = next.dataset.snapshotUrl;
        if (data.url) {
            const canonical = new URL(data.url, window.location.href);
            if (canonical.origin === window.location.origin && canonical.pathname === window.location.pathname) {
                const destination = canonical.href + window.location.hash;
                if (destination !== window.location.href) window.history.replaceState(window.history.state, '', destination);
            }
        }
        message('Antrean pesanan diperbarui.');
        window.dispatchEvent(new CustomEvent('ciks:order-queue-rendered', { detail: { revision: data.revision } }));
    };
    const sync = createOrderQueueSync({
        url: () => {
            const url = new URL(queue.dataset.snapshotUrl, window.location.href);
            url.searchParams.set('revision', queue.dataset.orderQueueRevision);
            return url.href;
        },
        apply: applySnapshot,
        onError: () => message('Antrean belum tersambung. Pembaruan akan dicoba kembali.', true),
        onExpired: () => message('Sesi berakhir. Silakan masuk ulang untuk memperbarui antrean.', true),
    });
    const check = () => { if (!document.hidden) sync.refresh(); };
    window.addEventListener('ciks:order-queue-updated', check);
    window.addEventListener('ciks:mobile-order-received', check);
    window.addEventListener('online', check);
    window.addEventListener('focus', check);
    document.addEventListener('visibilitychange', check);
    const timer = window.setInterval(check, 5000);
    window.addEventListener('pagehide', () => { clearInterval(timer); sync.stop(); });
    check();
}
