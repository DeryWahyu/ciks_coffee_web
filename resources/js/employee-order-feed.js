/** A full scan avoids losing late commits, reconnects, or a blocked WebSocket. */
export function createOrderFeed({ url, receive, reconcile, onStatus = () => {}, onExpired = () => {},
    fetcher = (...args) => fetch(...args), interval = 5000 }) {
    let checking = false;
    let timer;
    let controller;
    let stopped = false;

    async function poll() {
        if (checking || stopped) return;
        checking = true;
        controller = new AbortController();
        const startedAt = Date.now();
        const orders = [];
        try {
            let cursor = 0;
            do {
                const page = new URL(url, window.location.href);
                page.searchParams.set('after_id', String(cursor));
                const timeout = setTimeout(() => controller.abort(), 10000);
                let data;
                try {
                    const response = await fetcher(page.href, {
                        headers: { Accept: 'application/json' }, cache: 'no-store',
                        credentials: 'same-origin', signal: controller.signal,
                    });
                    if ([401, 403].includes(response.status)) { stop(); onExpired(); return; }
                    if (!response.ok) throw new Error('Notifikasi pesanan belum dapat diperiksa.');
                    data = await response.json();
                } finally { clearTimeout(timeout); }
                if (!Array.isArray(data.orders)) throw new Error('Respons notifikasi tidak valid.');
                orders.push(...data.orders);
                if (data.next_cursor === null) break;
                const next = Number(data.next_cursor);
                if (!Number.isSafeInteger(next) || next <= cursor) throw new Error('Urutan notifikasi tidak valid.');
                cursor = next;
            } while (!stopped);
            if (stopped) return;
            reconcile(orders, startedAt);
            for (const order of orders) receive({ order });
            onStatus(true);
        } catch {
            if (!stopped) onStatus(false);
        } finally { checking = false; }
    }

    function stop() {
        stopped = true;
        clearInterval(timer);
        controller?.abort();
    }

    return {
        poll,
        start() { if (timer || stopped) return; poll(); timer = setInterval(poll, interval); },
        stop,
    };
}
