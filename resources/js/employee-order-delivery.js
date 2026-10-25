/** Keep unplayed alerts pending; only acknowledge a completed or dismissed alert. */
export function createOrderDelivery({ sound, key, read = () => [], write = () => {}, locks,
    audible = () => true, onBusy = () => {}, now = () => Date.now() }) {
    const pending = new Map();
    const acknowledged = new Set();
    let flushing = false;
    let disposed = false;

    function isAcknowledged(id) {
        try {
            const stored = read();
            if (Array.isArray(stored)) for (const value of stored) acknowledged.add(String(value));
        } catch { /* Storage restrictions cannot disable notifications. */ }
        return acknowledged.has(id);
    }

    function acknowledge(id) {
        isAcknowledged(id);
        acknowledged.add(id);
        if (acknowledged.size > 1000) acknowledged.delete(acknowledged.values().next().value);
        try { write([...acknowledged]); } catch { /* Retain this tab's acknowledgement. */ }
        pending.delete(id);
    }

    async function playPending() {
        while (!disposed && audible() && sound.unlocked && pending.size) {
            const id = pending.keys().next().value;
            if (isAcknowledged(id)) { pending.delete(id); continue; }
            const completed = await new Promise(resolve => {
                const accepted = sound.notify({ onComplete: result => {
                    if (result.completed) acknowledge(id);
                    resolve(result.completed);
                } });
                if (!accepted) resolve(false);
            });
            if (!completed) return;
        }
    }

    async function flush() {
        if (flushing || disposed || !audible() || !sound.unlocked || !pending.size) return;
        flushing = true;
        onBusy(true);
        try {
            if (locks?.request) {
                let entered = false;
                try {
                    await locks.request(key, { ifAvailable: true }, async lock => {
                        if (!lock) return;
                        entered = true;
                        await playPending();
                    });
                } catch {
                    // Some browsers expose Web Locks but deny requests. Keep audio usable.
                    if (!entered) await playPending();
                }
            } else {
                await playPending();
            }
        } catch {
            // Leave the alert pending so the next poll or gesture can retry it.
        } finally {
            flushing = false;
            onBusy(false);
        }
    }

    return {
        get count() { return pending.size; },
        isAcknowledged,
        receive(order) {
            const id = String(order.id ?? order.order_number);
            if (!disposed && !isAcknowledged(id) && !pending.has(id)) pending.set(id, { order, receivedAt: now() });
            return flush();
        },
        reconcile(orders, startedAt) {
            const active = new Set(orders.map(order => String(order.id ?? order.order_number)));
            for (const [id, entry] of pending) {
                if (!active.has(id) && entry.receivedAt < startedAt) pending.delete(id);
            }
        },
        flush,
        dismiss() {
            for (const id of pending.keys()) acknowledge(id);
            sound.stop('stopped');
        },
        dispose() {
            disposed = true;
            sound.stop('interrupted');
        },
    };
}
