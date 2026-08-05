import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

/**
 * Initialize Laravel Echo with Pusher / Reverb configuration.
 * Prefers server-injected runtime variables (window.PUSHER_APP_KEY) so that 
 * production assets built locally don't hardcode dev keys.
 */
const pusherKey = (typeof window !== 'undefined' && window.PUSHER_APP_KEY) || import.meta.env.VITE_PUSHER_APP_KEY;
const pusherCluster = (typeof window !== 'undefined' && window.PUSHER_APP_CLUSTER) || import.meta.env.VITE_PUSHER_APP_CLUSTER || 'mt1';

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: pusherKey,
    cluster: pusherCluster,
    wsHost: import.meta.env.VITE_PUSHER_HOST ? import.meta.env.VITE_PUSHER_HOST : `ws-${pusherCluster}.pusher.com`,
    wsPort: import.meta.env.VITE_PUSHER_PORT ?? 80,
    wssPort: import.meta.env.VITE_PUSHER_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});
