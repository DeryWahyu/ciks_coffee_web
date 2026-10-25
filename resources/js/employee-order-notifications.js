import { createEmployeeOrderSound } from './employee-order-sound.js';
import { createOrderDelivery } from './employee-order-delivery.js';
import { createOrderFeed } from './employee-order-feed.js';

const config = window.CiksEmployeeNotifications;

if (config?.userId) {
    const nodes = {
        root: document.getElementById('order-notification-root'),
        trigger: document.getElementById('order-notification-trigger'),
        panel: document.getElementById('order-notification-panel'),
        badge: document.getElementById('order-notification-badge'),
        status: document.getElementById('order-notification-status'),
        soundStatus: document.getElementById('order-notification-sound-status'),
        lastOrder: document.getElementById('order-notification-last-order'),
        enable: document.getElementById('order-notification-enable-sound'),
        quickEnable: document.getElementById('order-notification-quick-enable'),
        stop: document.getElementById('order-notification-stop-sound'),
        mute: document.getElementById('order-notification-mute'),
        volume: document.getElementById('order-notification-volume'),
        volumeLabel: document.getElementById('order-notification-volume-value'),
    };
    const preferenceKey = `ciks:order-notification:${config.userId}`;
    const readSaved = (key, fallback) => {
        try { return JSON.parse(localStorage.getItem(key)) ?? fallback; } catch { return fallback; }
    };
    const saved = readSaved(preferenceKey, {});
    const clampVolume = value => Number.isFinite(Number(value)) ? Math.min(1, Math.max(0, Number(value))) : 1;
    const state = {
        muted: Boolean(saved?.muted), volume: clampVolume(saved?.volume ?? 1),
        enabled: Boolean(saved?.enabled), unlocked: false, playing: false,
        activating: false, claiming: 0, voiceError: false, soundError: '', unread: 0, lastOrder: null,
        realtimeConnected: false, feedHealthy: false, expired: false,
    };
    let delivery;
    let leaving = false;
    const received = new Set();
    const persist = () => {
        try { localStorage.setItem(preferenceKey, JSON.stringify({ muted: state.muted, volume: state.volume, enabled: state.enabled })); }
        catch { /* Audio remains usable when browser storage is unavailable. */ }
    };
    const setConnectionStatus = (label, tone = 'text-caramel') => {
        if (!nodes.status) return;
        nodes.status.textContent = label;
        nodes.status.className = `text-[0.65rem] ${tone}`;
    };
    const updateUi = () => {
        if (nodes.badge) {
            nodes.badge.textContent = state.unread > 99 ? '99+' : String(state.unread);
            nodes.badge.classList.toggle('hidden', state.unread === 0);
        }
        nodes.trigger?.setAttribute('aria-label', state.unread ? `${state.unread} pesanan baru` : 'Notifikasi pesanan');
        if (nodes.mute) nodes.mute.checked = state.muted;
        if (nodes.volume) { nodes.volume.value = String(Math.round(state.volume * 100)); nodes.volume.disabled = state.muted; }
        if (nodes.volumeLabel) nodes.volumeLabel.textContent = `${Math.round(state.volume * 100)}%`;
        if (nodes.enable) {
            nodes.enable.textContent = state.activating ? 'Mengaktifkan suara…'
                : state.playing ? 'Notifikasi sedang berbunyi…'
                    : state.unlocked && !state.muted ? 'Coba suara (3 kali)' : 'Aktifkan suara';
            nodes.enable.disabled = state.activating || state.playing || state.expired;
        }
        nodes.stop?.classList.toggle('hidden', !state.playing);
        if (nodes.quickEnable) {
            nodes.quickEnable.classList.toggle('hidden', state.unlocked && !state.muted && state.volume > 0);
            nodes.quickEnable.textContent = state.muted || state.volume === 0 ? 'Nyalakan suara' : 'Aktifkan suara';
            nodes.quickEnable.disabled = state.activating || state.expired;
        }
        if (nodes.soundStatus) {
            nodes.soundStatus.textContent = state.muted ? 'Notifikasi suara dibisukan.'
                : state.soundError || (!state.unlocked ? 'Klik Aktifkan suara agar pesanan dapat terdengar.'
                    : state.voiceError ? 'Ucapan gagal dimuat; bel tetap berbunyi 3 kali.'
                        : state.bellError ? 'Rekaman bel gagal dimuat; bel cadangan tetap aktif.'
                            : 'Bel ramai dan ucapan “Ada pesanan baru masuk” diulang 3 kali.');
        }
        if (nodes.lastOrder) {
            nodes.lastOrder.textContent = state.lastOrder
                ? `${state.lastOrder.order_number} · ${state.lastOrder.customer_name} · ${state.lastOrder.formatted_total}`
                : 'Belum ada pesanan mobile baru pada sesi ini.';
        }
    };
    const syncPlayback = () => {
        const wasPlaying = nodes.root?.dataset.orderSoundPlaying === 'true';
        const active = state.playing || state.claiming > 0;
        if (nodes.root) nodes.root.dataset.orderSoundPlaying = String(active);
        updateUi();
        if (wasPlaying && !active && !leaving) window.dispatchEvent(new Event('ciks:order-sound-finished'));
    };
    const sound = createEmployeeOrderSound({
        url: config.soundUrl,
        bellUrl: config.bellUrl,
        onChange: audio => {
            Object.assign(state, audio);
            if (audio.unlocked) state.soundError = '';
            syncPlayback();
            if (audio.unlocked) delivery?.flush();
        },
    });
    sound.setVolume(state.muted ? 0 : state.volume);
    const deliveredKey = `${preferenceKey}:delivered-v2`;
    delivery = createOrderDelivery({
        sound, key: `${preferenceKey}:delivery-v2`, locks: navigator.locks,
        read: () => readSaved(deliveredKey, []),
        write: keys => localStorage.setItem(deliveredKey, JSON.stringify(keys)),
        audible: () => !state.muted && state.volume > 0 && !state.expired,
        onBusy: busy => { state.claiming = busy ? 1 : 0; syncPlayback(); },
    });

    const unlockSound = async () => {
        try {
            await sound.unlock();
            state.enabled = true;
            state.soundError = '';
            persist();
            delivery.flush();
        } catch (error) {
            state.soundError = error.message || 'Browser belum mengizinkan audio.';
        }
        updateUi();
    };
    const activateSound = async () => {
        if (state.activating || state.playing || state.expired) return;
        state.activating = true;
        state.muted = false;
        if (state.volume === 0) state.volume = 1;
        sound.setVolume(state.volume);
        updateUi();
        try {
            await unlockSound();
            if (!delivery.count && !state.playing) sound.notify();
        }
        finally { state.activating = false; updateUi(); }
    };
    const positionPanel = () => {
        if (!nodes.panel || nodes.panel.classList.contains('hidden')) return;
        nodes.panel.style.transform = '';
        const bounds = nodes.panel.getBoundingClientRect();
        const left = Math.max(12, Math.min(bounds.left, window.innerWidth - bounds.width - 12));
        nodes.panel.style.transform = `translateX(${left - bounds.left}px)`;
    };
    const openPanel = (open) => {
        if (!nodes.panel || !nodes.trigger) return;
        nodes.panel.classList.toggle('hidden', !open);
        nodes.trigger.setAttribute('aria-expanded', String(open));
        if (open) { state.unread = 0; updateUi(); positionPanel(); }
    };
    const handleOrder = (payload) => {
        const order = payload?.order;
        if (!order?.order_number || state.expired) return;
        const key = String(order.id ?? order.order_number);
        if (delivery.isAcknowledged(key)) return;
        delivery.receive(order);
        if (received.has(key)) return;
        received.add(key);
        if (received.size > 100) received.delete(received.values().next().value);
        state.unread++;
        state.lastOrder = order;
        updateUi();
        const message = `${order.order_number} dari ${order.customer_name} · ${order.formatted_total} (${order.payment_method})`;
        window.CiksAlert?.notify(message, 'info', 'Pesanan mobile baru');
        window.dispatchEvent(new CustomEvent('ciks:mobile-order-received', { detail: order }));
    };

    nodes.trigger?.addEventListener('click', () => openPanel(nodes.panel?.classList.contains('hidden')));
    window.addEventListener('resize', positionPanel);
    nodes.enable?.addEventListener('click', activateSound);
    nodes.quickEnable?.addEventListener('click', activateSound);
    nodes.stop?.addEventListener('click', () => delivery.dismiss());
    nodes.mute?.addEventListener('change', () => {
        state.muted = nodes.mute.checked;
        if (state.muted) sound.stop('muted');
        sound.setVolume(state.muted ? 0 : state.volume);
        persist(); updateUi();
        if (!state.muted) unlockSound();
    });
    nodes.volume?.addEventListener('input', () => {
        state.volume = clampVolume(Number(nodes.volume.value) / 100);
        sound.setVolume(state.muted ? 0 : state.volume);
        if (state.volume === 0) sound.stop('muted');
        persist(); updateUi();
        if (state.volume > 0) delivery.flush();
    });
    document.addEventListener('click', (event) => {
        if (nodes.panel && nodes.trigger && !nodes.panel.contains(event.target) && !nodes.trigger.contains(event.target)) openPanel(false);
    });
    const activateAfterGesture = () => {
        if (!state.expired && !sound.unlocked) unlockSound();
    };
    document.addEventListener('pointerdown', activateAfterGesture, { passive: true });
    document.addEventListener('keydown', activateAfterGesture);

    const updateConnection = () => {
        if (state.expired) setConnectionStatus('Sesi berakhir. Silakan masuk ulang.', 'text-red-500');
        else if (state.realtimeConnected) setConnectionStatus('Realtime aktif', 'text-emerald-600');
        else if (state.feedHealthy) setConnectionStatus('Pesanan dipantau otomatis (5 detik)', 'text-emerald-600');
        else setConnectionStatus('Mencoba menyambung notifikasi…', 'text-amber-700');
    };
    const feed = config.feedUrl ? createOrderFeed({
        url: config.feedUrl, receive: handleOrder,
        reconcile: (orders, began) => delivery.reconcile(orders, began),
        onStatus: healthy => { state.feedHealthy = healthy; updateConnection(); },
        onExpired: () => {
            state.expired = true;
            delivery.dispose();
            updateUi(); updateConnection();
        },
    }) : null;
    const recover = () => {
        if (state.expired || leaving) return;
        feed?.poll();
        if (state.enabled && !state.muted && !sound.unlocked) unlockSound();
        else delivery.flush();
    };
    window.addEventListener('online', recover);
    window.addEventListener('focus', recover);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) recover(); });
    window.addEventListener('storage', event => {
        if (event.key === deliveredKey) delivery.flush();
    });
    window.addEventListener('pagehide', () => {
        leaving = true;
        feed?.stop();
        delivery.dispose();
    });
    window.addEventListener('pageshow', event => {
        // A restored page contains disposed audio and polling objects.
        if (event.persisted) window.location.reload();
    });

    // Restore previously permitted audio; every later gesture can retry blocked audio.
    if (state.enabled && !state.muted) unlockSound();
    updateUi(); updateConnection();
    feed?.start();
    try {
        const echo = window.createCiksEcho?.(config.reverb);
        const channel = echo?.private('karyawan.orders');
        channel?.listen('.mobile-order.created', handleOrder);
        const updateQueue = payload => window.dispatchEvent(new CustomEvent('ciks:order-queue-updated', { detail: payload }));
        channel?.listen('.order-queue.updated', updateQueue);
        channel?.subscribed?.(() => { feed?.poll(); updateQueue(); });
        const connection = echo?.connector?.pusher?.connection;
        connection?.bind('connected', () => {
            state.realtimeConnected = true; updateConnection(); feed?.poll(); updateQueue();
        });
        const disconnected = () => { state.realtimeConnected = false; updateConnection(); feed?.poll(); };
        connection?.bind('disconnected', disconnected);
        connection?.bind('unavailable', disconnected);
        connection?.bind('error', disconnected);
    } catch {
        // Polling remains active even if a WebSocket cannot be constructed.
        state.realtimeConnected = false; updateConnection();
    }
}
