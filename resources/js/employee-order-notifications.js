const config = window.CiksEmployeeNotifications;

if (config?.userId && config?.reverb) {
    const nodes = {
        trigger: document.getElementById('order-notification-trigger'),
        panel: document.getElementById('order-notification-panel'),
        badge: document.getElementById('order-notification-badge'),
        status: document.getElementById('order-notification-status'),
        lastOrder: document.getElementById('order-notification-last-order'),
        enable: document.getElementById('order-notification-enable-sound'),
        mute: document.getElementById('order-notification-mute'),
        volume: document.getElementById('order-notification-volume'),
    };
    const preferenceKey = `ciks:order-notification:${config.userId}`;
    const saved = (() => {
        try { return JSON.parse(localStorage.getItem(preferenceKey) || '{}'); } catch { return {}; }
    })();
    const state = {
        muted: Boolean(saved.muted),
        volume: Math.min(1, Math.max(0, Number(saved.volume ?? 0.65))),
        soundUnlocked: false,
        unread: 0,
        lastOrder: null,
        audioContext: null,
    };

    const persist = () => localStorage.setItem(preferenceKey, JSON.stringify({ muted: state.muted, volume: state.volume }));
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
        if (nodes.trigger) nodes.trigger.setAttribute('aria-label', state.unread ? `${state.unread} pesanan baru` : 'Notifikasi pesanan');
        if (nodes.mute) nodes.mute.checked = state.muted;
        if (nodes.volume) { nodes.volume.value = String(Math.round(state.volume * 100)); nodes.volume.disabled = state.muted; }
        if (nodes.enable) {
            nodes.enable.textContent = state.soundUnlocked ? (state.muted ? 'Suara dimatikan' : 'Suara aktif') : 'Aktifkan suara';
            nodes.enable.classList.toggle('opacity-60', state.soundUnlocked && state.muted);
        }
        if (nodes.lastOrder) {
            nodes.lastOrder.textContent = state.lastOrder
                ? `${state.lastOrder.order_number} · ${state.lastOrder.customer_name} · ${state.lastOrder.formatted_total}`
                : 'Belum ada pesanan mobile baru pada sesi ini.';
        }
    };
    const getAudioContext = () => {
        if (!state.audioContext) {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) return null;
            state.audioContext = new AudioContext();
        }
        return state.audioContext;
    };
    const tone = (context, frequency, start, duration, gain) => {
        const oscillator = context.createOscillator();
        const volume = context.createGain();
        oscillator.type = 'sine';
        oscillator.frequency.setValueAtTime(frequency, start);
        volume.gain.setValueAtTime(0.0001, start);
        volume.gain.exponentialRampToValueAtTime(gain, start + 0.015);
        volume.gain.exponentialRampToValueAtTime(0.0001, start + duration);
        oscillator.connect(volume).connect(context.destination);
        oscillator.start(start);
        oscillator.stop(start + duration + 0.02);
    };
    const playChime = async (force = false) => {
        if (state.muted || (!state.soundUnlocked && !force)) return;
        const context = getAudioContext();
        if (!context) return;
        try {
            if (context.state !== 'running') await context.resume();
            const now = context.currentTime;
            const gain = 0.11 * state.volume;
            tone(context, 659.25, now, 0.16, gain);
            tone(context, 880, now + 0.19, 0.26, gain);
        } catch {
            state.soundUnlocked = false;
            updateUi();
        }
    };
    const activateSound = async () => {
        const context = getAudioContext();
        if (!context) {
            setConnectionStatus('Audio tidak didukung browser ini.', 'text-red-500');
            return;
        }
        try {
            await context.resume();
            state.soundUnlocked = true;
            state.muted = false;
            persist();
            updateUi();
            await playChime(true);
        } catch {
            setConnectionStatus('Browser menolak pemutaran suara.', 'text-red-500');
        }
    };
    const openPanel = (open) => {
        if (!nodes.panel || !nodes.trigger) return;
        nodes.panel.classList.toggle('hidden', !open);
        nodes.trigger.setAttribute('aria-expanded', String(open));
        if (open) { state.unread = 0; updateUi(); }
    };
    const handleOrder = (payload) => {
        const order = payload?.order;
        if (!order?.order_number) return;
        state.unread += 1;
        state.lastOrder = order;
        updateUi();
        const message = `${order.order_number} dari ${order.customer_name} · ${order.formatted_total} (${order.payment_method})`;
        window.CiksAlert?.notify(message, 'info', 'Pesanan mobile baru');
        playChime();
        window.dispatchEvent(new CustomEvent('ciks:mobile-order-received', { detail: order }));
    };

    nodes.trigger?.addEventListener('click', () => openPanel(nodes.panel?.classList.contains('hidden')));
    nodes.enable?.addEventListener('click', activateSound);
    nodes.mute?.addEventListener('change', () => { state.muted = nodes.mute.checked; persist(); updateUi(); });
    nodes.volume?.addEventListener('input', () => { state.volume = Math.min(1, Math.max(0, Number(nodes.volume.value) / 100)); persist(); updateUi(); });
    document.addEventListener('click', (event) => {
        if (nodes.panel && nodes.trigger && !nodes.panel.contains(event.target) && !nodes.trigger.contains(event.target)) openPanel(false);
    });

    updateUi();
    const echo = window.createCiksEcho?.(config.reverb);
    if (!echo) {
        setConnectionStatus('Realtime belum tersedia.', 'text-red-500');
    } else {
        setConnectionStatus('Menghubungkan realtime…');
        echo.private('karyawan.orders').listen('.mobile-order.created', handleOrder);
        const connection = echo.connector?.pusher?.connection;
        connection?.bind('connected', () => setConnectionStatus('Realtime aktif', 'text-emerald-600'));
        connection?.bind('disconnected', () => setConnectionStatus('Koneksi realtime terputus.', 'text-red-500'));
        connection?.bind('error', () => setConnectionStatus('Realtime belum tersambung.', 'text-red-500'));
    }
}