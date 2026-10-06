import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const reverbKey = import.meta.env.VITE_REVERB_APP_KEY;
const reverbHost = String(import.meta.env.VITE_REVERB_HOST ?? window.location.hostname)
    .replace(/^https?:\/\//, '')
    .replace(/\/+$/, '');

if (! reverbKey) {
    console.warn('[Queue] VITE_REVERB_APP_KEY tidak ditemukan. Real-time nonaktif — jalankan npm run build setelah mengisi .env');
} else {
    const realtimeState = window.__queueRealtimeState ??= {
        echo: null,
        channel: null,
        subscribed: false,
        lifecycleListenersBound: false,
        connectionListenersBound: false,
        status: 'initialized',
    };

    if (! realtimeState.echo) {
        realtimeState.echo = new Echo({
            broadcaster: 'reverb',
            key: reverbKey,
            wsHost: reverbHost,
            wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 8080),
            wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 443),
            forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
            enabledTransports: ['ws', 'wss'],
        });
    }

    window.Echo = realtimeState.echo;

    const updateRealtimeStatus = (status) => {
        realtimeState.status = status;
        document.documentElement.dataset.realtimeStatus = status;
        window.dispatchEvent(new CustomEvent('queue-realtime-status', { detail: { status } }));
    };

    const bindConnectionLifecycle = () => {
        const connection = realtimeState.echo.connector?.pusher?.connection;

        if (! connection || realtimeState.connectionListenersBound) {
            return;
        }

        connection.bind('state_change', ({ current }) => {
            updateRealtimeStatus(current);

            if (current === 'connected') {
                console.info('[Queue] Echo tersambung ke Reverb');
            } else if (current === 'disconnected' || current === 'failed' || current === 'unavailable') {
                console.warn(`[Queue] Status koneksi Reverb: ${current}`);
            }
        });
        connection.bind('error', (error) => {
            console.error('[Queue] Koneksi Reverb gagal', error);
        });
        realtimeState.connectionListenersBound = true;
        updateRealtimeStatus(connection.state ?? 'initialized');
    };

    const subscribeToQueueChannel = () => {
        const requiresRealtime = document.querySelector('[data-queue-realtime]');

        if (! requiresRealtime || realtimeState.subscribed) {
            return;
        }

        realtimeState.subscribed = true;

        realtimeState.channel = realtimeState.echo.channel('display-board')
            .listen('.display.updated', (payload) => {
                const announcement = payload?.announcement;
                const isDisplayClient = document.querySelector('[data-queue-realtime="display"]');

                if (isDisplayClient && announcement?.message) {
                    window.dispatchEvent(new CustomEvent('queue-display-announcement', { detail: {
                        message: announcement.message,
                        ticket_number: announcement.ticket_number,
                        service_name: announcement.service_name,
                        service_code: announcement.service_code,
                        counter_name: announcement.counter_name,
                        counter_code: announcement.counter_code,
                        call_id: announcement.call_id,
                        called_at: announcement.called_at,
                    } }));
                }

                // Queue the announcement before Livewire refreshes the board so the
                // current-call card stays aligned with the audio FIFO.
                window.Livewire.dispatch('queue-updated');
            });

        bindConnectionLifecycle();
    };

    if (! realtimeState.lifecycleListenersBound) {
        document.addEventListener('livewire:init', subscribeToQueueChannel);
        document.addEventListener('livewire:navigated', subscribeToQueueChannel);
        realtimeState.lifecycleListenersBound = true;
    }
}
