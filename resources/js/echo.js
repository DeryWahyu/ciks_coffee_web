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
        wsHost: host,
        wsPort: port,
        wssPort: port,
        forceTLS: secure,
        // Pusher memakai nama transport `ws`; forceTLS akan menaikkannya ke WSS.
        // Memilih `wss` saja membuat strategi transport dianggap tidak didukung.
        enabledTransports: ['ws'],
        authEndpoint: config.authEndpoint || '/broadcasting/auth',
        auth: {
            headers: csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {},
        },
    });

    return window.Echo;
};