/** Serialize snapshots and check again if an event arrives during a request. */
export function createOrderQueueSync({ url, apply, onError = () => {}, onExpired = () => {},
    fetcher = (...args) => fetch(...args) }) {
    let checking = false;
    let requested = false;
    let stopped = false;
    let controller;

    async function refresh() {
        if (stopped) return;
        if (checking) { requested = true; return; }
        checking = true;
        try {
            do {
                requested = false;
                controller = new AbortController();
                const timeout = setTimeout(() => controller.abort(), 10000);
                try {
                    const response = await fetcher(url(), {
                        headers: { Accept: 'application/json' }, credentials: 'same-origin',
                        cache: 'no-store', signal: controller.signal,
                    });
                    if ([401, 403].includes(response.status)) { stop(); onExpired(); return; }
                    if (response.status === 204) continue;
                    if (!response.ok) throw new Error('Antrean belum dapat diperbarui.');
                    const data = await response.json();
                    if (!data.revision || typeof data.html !== 'string') throw new Error('Respons antrean tidak valid.');
                    if (!stopped) apply(data);
                } finally { clearTimeout(timeout); }
            } while (requested && !stopped);
        } catch {
            if (!stopped) onError();
        } finally { checking = false; }
    }

    function stop() { stopped = true; controller?.abort(); }
    return { refresh, stop };
}
