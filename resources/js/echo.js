import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.createCiksEcho = (config = {}) => {
    const host = config.host || window.location.hostname;
    if (!config.key || !host) return null;

    window.Echo?.disconnect();

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const secure = config.scheme ? config.scheme === 'https' : window.location.protocol === 'https:';
    const port = Number(config.port || window.location.port || (secure ? 443 : 80));

    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: config.key,
        // pusher-js v8 tetap mewajibkan cluster meski host Reverb dikustom.
        // Nilai ini tidak dipakai untuk routing karena wsHost/wssPort ditentukan di bawah.
        cluster: 'mt1',
        wsHost: host,
        wsPort: port,
        wssPort: port,
        forceTLS: secure,
        enabledTransports: secure ? ['wss'] : ['ws', 'wss'],
        authEndpoint: config.authEndpoint || '/broadcasting/auth',
        auth: {
            headers: csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {},
        },
    });

    return window.Echo;
};