/** Keep fast recovery polling; reduce idle requests only after a private channel subscribes. */
export function createAdaptivePoller({ task, interval = 5000, connectedInterval = 30000,
    schedule = (fn, delay) => setTimeout(fn, delay), cancel = id => clearTimeout(id) }) {
    let active = false;
    let connected = false;
    let timer;
    function plan() {
        cancel(timer);
        if (!active) return;
        timer = schedule(async () => {
            timer = undefined;
            try { await task(); } catch { /* The next check must survive a rejected request. */ }
            finally { plan(); }
        }, connected ? connectedInterval : interval);
    }
    return {
        start() { if (active) return; active = true; plan(); },
        setConnected(value) { connected = Boolean(value); plan(); },
        stop() { active = false; cancel(timer); timer = undefined; },
    };
}
