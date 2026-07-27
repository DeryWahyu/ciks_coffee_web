import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.createCiksEcho = (config = {}) => {
    if (!config.key || !config.host) return null;

    window.Echo?.disconnect();

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const secure = config.scheme === 'https';
    const port = Number(config.port || (secure ? 443 : 80));

    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: config.key,
        wsHost: config.host,
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