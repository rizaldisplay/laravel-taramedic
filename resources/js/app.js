import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import './echo';
import { createReceiptPrinterController } from './kiosk/receipt-printer';
import { initializeOfflineTts, offlineTtsStatus, synthesizeOfflineTts } from './tts/offline-tts';
import {
    formatTicketNumberForSpeech,
    indonesianDigits,
    indonesianLetters,
    spokenTicketCharacters,
} from './tts/ticket-pronunciation';

Alpine.data('kioskShell', (config = '', fallbackIdleSeconds = 60) => {
    const options = typeof config === 'object' && config !== null
        ? config
        : { institutionName: config, idleSeconds: fallbackIdleSeconds };

    return {
        ...createReceiptPrinterController(options),
        idleTimer: null,
        idleSeconds: Number(options.idleSeconds || fallbackIdleSeconds || 60),

        async boot() {
            await this.init();
            this.resetIdle();
        },

        resetIdle() {
            clearTimeout(this.idleTimer);

            if (this.$wire?.issuedTicket) {
                return;
            }

            this.idleTimer = setTimeout(() => {
                this.$wire?.resetKiosk?.();
            }, this.idleSeconds * 1000);
        },
    };
});

Alpine.data('kioskSuccess', (resetSeconds = 15) => ({
    seconds: resetSeconds,
    dateTime: '',
    countdownTimer: null,
    clockTimer: null,

    boot() {
        this.updateDateTime();
        this.clockTimer = setInterval(() => this.updateDateTime(), 1000);
        this.countdownTimer = setInterval(() => {
            if (this.seconds > 0) {
                this.seconds -= 1;

                return;
            }

            clearInterval(this.countdownTimer);
            this.$wire?.resetKiosk?.();
        }, 1000);
    },

    updateDateTime() {
        this.dateTime = new Intl.DateTimeFormat('id-ID', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        }).format(new Date()).replace(' pukul', '  | ');
    },

    destroy() {
        clearInterval(this.countdownTimer);
        clearInterval(this.clockTimer);
    },
}));

Alpine.data('adminToast', (duration = 5200) => ({
    visible: true,
    timeoutId: null,

    init() {
        this.timeoutId = setTimeout(() => this.close(), duration);
    },

    close() {
        this.visible = false;
        clearTimeout(this.timeoutId);
    },

    destroy() {
        clearTimeout(this.timeoutId);
    },
}));

Alpine.data('displayTvRecentCalls', () => ({
    positionsBeforeUpdate: null,

    init() {
        Livewire.hook('commit', ({ component, succeed }) => {
            const flow = this.$el.querySelector('.display-tv-recent-flow');

            if (! flow) {
                return;
            }

            this.positionsBeforeUpdate = this.capturePositions(flow);

            succeed(() => {
                requestAnimationFrame(() => {
                    this.applyFlip(flow);
                    this.positionsBeforeUpdate = null;
                });
            });
        });
    },

    capturePositions(flow) {
        const map = new Map();

        flow.querySelectorAll('[data-recent-ticket]').forEach((node) => {
            map.set(node.dataset.recentTicket, {
                left: node.offsetLeft,
                top: node.offsetTop,
            });
        });

        return map;
    },

    applyFlip(flow) {
        const previous = this.positionsBeforeUpdate ?? new Map();

        flow.querySelectorAll('[data-recent-ticket]').forEach((node) => {
            const key = node.dataset.recentTicket;
            const before = previous.get(key);

            if (! before) {
                if (previous.size > 0) {
                    node.classList.add('display-tv-recent-card--enter');
                    setTimeout(() => node.classList.remove('display-tv-recent-card--enter'), 650);
                }

                return;
            }

            const deltaX = before.left - node.offsetLeft;
            const deltaY = before.top - node.offsetTop;

            if (Math.abs(deltaX) < 1 && Math.abs(deltaY) < 1) {
                return;
            }

            node.animate(
                [
                    { transform: `translate(${deltaX}px, ${deltaY}px)` },
                    { transform: 'translate(0, 0)' },
                ],
                {
                    duration: 550,
                    easing: 'cubic-bezier(0.22, 1, 0.36, 1)',
                },
            );
        });
    },
}));

Alpine.data('displayTvTicker', () => ({
    pixelsPerSecond: 35,
    resizeObserver: null,
    measureFrame: null,

    async init() {
        await document.fonts?.ready;

        this.$nextTick(() => {
            this.scheduleDurationUpdate();

            if (! window.ResizeObserver) {
                return;
            }

            const content = this.$el.querySelector('.display-tv-ticker-content');

            if (! content) {
                return;
            }

            this.resizeObserver = new ResizeObserver(() => this.scheduleDurationUpdate());
            this.resizeObserver.observe(content);
        });
    },

    scheduleDurationUpdate() {
        cancelAnimationFrame(this.measureFrame);
        this.measureFrame = requestAnimationFrame(() => {
            this.measureFrame = requestAnimationFrame(() => this.updateDuration());
        });
    },

    updateDuration() {
        const content = this.$el.querySelector('.display-tv-ticker-content');

        if (! content) {
            return;
        }

        const distance = content.scrollWidth;
        const duration = Math.max(36, distance / this.pixelsPerSecond);

        this.$el.style.setProperty('--display-tv-ticker-duration', `${duration.toFixed(2)}s`);
    },

    destroy() {
        this.resizeObserver?.disconnect();
        cancelAnimationFrame(this.measureFrame);
    },
}));

const displayKioskAutostartEnabled = new URLSearchParams(window.location.search)
    .get('autostart') === '1';

window.__displayAudioUnlocked = displayKioskAutostartEnabled
    || sessionStorage.getItem('display-audio-unlocked') === '1';
window.__displayAnnouncementQueueActive = false;

const AUDIO_PLAY_TIMEOUT_MS = 12000;
const VOICE_WAIT_TIMEOUT_MS = 1500;
const INDONESIAN_TTS_LANGUAGE = 'id-ID';
const DEFAULT_ANNOUNCEMENT_SETTINGS = {
    voice_mode: 'offline_tts',
    announcement_enabled: true,
    audio_volume: 100,
    repeat_count: 1,
    opening_enabled: true,
    closing_enabled: true,
    cue_gap_ms: 0,
    repeat_pause_ms: 800,
    speak_zero_digits: true,
    tts_speed: 0.8,
    tts_style: 'natural',
    online_pitch: 1,
};

const toBoolean = (value, fallback = false) => {
    if (typeof value === 'boolean') {
        return value;
    }

    if (value === '1' || value === 1 || value === 'true') {
        return true;
    }

    if (value === '0' || value === 0 || value === 'false') {
        return false;
    }

    return fallback;
};

const boundedNumber = (value, fallback, min, max) => {
    const numericValue = Number(value);

    return Math.min(max, Math.max(min, Number.isFinite(numericValue) ? numericValue : fallback));
};

const normalizeAnnouncementSettings = (settings = {}) => ({
    voice_mode: settings.voice_mode === 'online_tts' ? 'online_tts' : 'offline_tts',
    announcement_enabled: toBoolean(settings.announcement_enabled, true),
    audio_volume: boundedNumber(settings.audio_volume, 100, 0, 100),
    repeat_count: boundedNumber(settings.repeat_count, 1, 1, 3),
    opening_enabled: toBoolean(settings.opening_enabled, true),
    closing_enabled: toBoolean(settings.closing_enabled, true),
    cue_gap_ms: boundedNumber(settings.cue_gap_ms, 0, 0, 500),
    repeat_pause_ms: boundedNumber(settings.repeat_pause_ms, 800, 0, 5000),
    speak_zero_digits: toBoolean(settings.speak_zero_digits, true),
    tts_speed: boundedNumber(settings.tts_speed, 0.8, 0.65, 1),
    tts_style: ['calm', 'expressive'].includes(settings.tts_style) ? settings.tts_style : 'natural',
    online_pitch: boundedNumber(settings.online_pitch, 1, 0.7, 1.3),
});

const currentAnnouncementSettings = () => {
    const display = document.querySelector('[data-announcement-settings]');

    if (! display) {
        return normalizeAnnouncementSettings(DEFAULT_ANNOUNCEMENT_SETTINGS);
    }

    try {
        return normalizeAnnouncementSettings(JSON.parse(display.dataset.announcementSettings || '{}'));
    } catch {
        return normalizeAnnouncementSettings(DEFAULT_ANNOUNCEMENT_SETTINGS);
    }
};

const createAnnouncementAudio = (source, volume = 1) => {
    const audio = new Audio(source);
    audio.preload = 'auto';
    audio.volume = boundedNumber(volume, 1, 0, 1);

    return audio;
};

const announcementAudioState = {
    voicesPromise: null,
    cueWarmPromise: null,
    cuesUnlocked: false,
    openingAudio: createAnnouncementAudio('/sounds/opening.mp3'),
    closingAudio: createAnnouncementAudio('/sounds/closing.mp3'),
};

const wait = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

const estimateAnnouncementMs = (message = '') => Math.min(
    45000,
    Math.max(8000, Math.round(String(message).trim().length * 150) + 3000),
);

const getOnlineTtsVoices = () => {
    if (! window.speechSynthesis) {
        return Promise.resolve([]);
    }

    if (announcementAudioState.voicesPromise) {
        return announcementAudioState.voicesPromise;
    }

    announcementAudioState.voicesPromise = new Promise((resolve) => {
        const initialVoices = window.speechSynthesis.getVoices();

        if (initialVoices.length > 0) {
            resolve(initialVoices);

            return;
        }

        const finish = () => {
            clearTimeout(timeoutId);
            window.speechSynthesis.removeEventListener('voiceschanged', finish);
            resolve(window.speechSynthesis.getVoices());
        };
        const timeoutId = setTimeout(finish, VOICE_WAIT_TIMEOUT_MS);

        window.speechSynthesis.addEventListener('voiceschanged', finish, { once: true });
    });

    return announcementAudioState.voicesPromise;
};

const scoreOnlineTtsVoice = (voice) => {
    const language = String(voice?.lang ?? '').toLowerCase();
    const name = String(voice?.name ?? '').toLowerCase();
    let score = 0;

    if (language === 'id-id') score += 120;
    else if (language.startsWith('id')) score += 100;
    else if (language.startsWith('ms')) score += 40;
    if (name.includes('indonesia') || name.includes('bahasa')) score += 50;
    if (name.includes('natural') || name.includes('neural') || name.includes('online')) score += 30;
    if (name.includes('female') || name.includes('perempuan')) score += 5;
    if (language.startsWith('en')) score -= 80;

    return score;
};

const selectOnlineTtsVoice = (voices) => [...voices]
    .sort((left, right) => scoreOnlineTtsVoice(right) - scoreOnlineTtsVoice(left))[0] ?? null;

const speakOnlineTts = async (message, settings, preparedVoice = null) => {
    if (! window.speechSynthesis || typeof SpeechSynthesisUtterance === 'undefined') {
        throw new Error('Browser ini tidak menyediakan Web Speech API.');
    }

    const voice = preparedVoice ?? selectOnlineTtsVoice(await getOnlineTtsVoices());

    await new Promise((resolve, reject) => {
        const utterance = new SpeechSynthesisUtterance(message);
        let settled = false;

        utterance.lang = voice?.lang ?? INDONESIAN_TTS_LANGUAGE;
        utterance.rate = settings.tts_speed;
        utterance.pitch = settings.online_pitch;
        utterance.volume = settings.audio_volume / 100;

        if (voice) {
            utterance.voice = voice;
        }

        const finish = (error = null) => {
            if (settled) {
                return;
            }

            settled = true;
            clearTimeout(timeoutId);
            error ? reject(error) : resolve();
        };
        const timeoutId = setTimeout(() => finish(), estimateAnnouncementMs(message));

        utterance.onend = () => finish();
        utterance.onerror = (event) => finish(new Error(`TTS online gagal: ${event.error || 'unknown'}`));
        window.speechSynthesis.cancel();
        window.speechSynthesis.speak(utterance);
    });
};

const warmAnnouncementAudio = async () => {
    if (announcementAudioState.cuesUnlocked) {
        return true;
    }

    if (announcementAudioState.cueWarmPromise) {
        return announcementAudioState.cueWarmPromise;
    }

    const warmCue = async (audio) => {
        const previouslyMuted = audio.muted;

        try {
            audio.muted = true;
            audio.currentTime = 0;
            await audio.play();
            audio.pause();
            audio.currentTime = 0;

            return true;
        } catch {
            return false;
        } finally {
            audio.muted = previouslyMuted;
        }
    };

    announcementAudioState.cueWarmPromise = Promise.all([
        warmCue(announcementAudioState.openingAudio),
        warmCue(announcementAudioState.closingAudio),
    ]).then((results) => {
        announcementAudioState.cuesUnlocked = results.some(Boolean);

        return announcementAudioState.cuesUnlocked;
    }).finally(() => {
        if (! announcementAudioState.cuesUnlocked) {
            announcementAudioState.cueWarmPromise = null;
        }
    });

    return announcementAudioState.cueWarmPromise;
};

const playAnnouncementCue = async (audio, volume = 1) => {
    if (! audio) {
        return;
    }

    await new Promise((resolve) => {
        let settled = false;
        let timeoutId = null;
        const finish = () => {
            if (settled) {
                return;
            }

            settled = true;
            clearTimeout(timeoutId);
            audio.removeEventListener('ended', finish);
            audio.removeEventListener('error', finish);
            resolve();
        };

        audio.pause();
        audio.currentTime = 0;
        audio.volume = boundedNumber(volume, 1, 0, 1);
        audio.addEventListener('ended', finish, { once: true });
        audio.addEventListener('error', finish, { once: true });

        timeoutId = setTimeout(finish, AUDIO_PLAY_TIMEOUT_MS);

        try {
            const playPromise = audio.play();

            if (playPromise && typeof playPromise.catch === 'function') {
                playPromise.catch(finish);
            }
        } catch {
            finish();
        }
    });
};

const warmAnnouncementAudioOnInteraction = () => {
    warmAnnouncementAudio().then((unlocked) => {
        if (! unlocked) {
            return;
        }

        document.removeEventListener('pointerdown', warmAnnouncementAudioOnInteraction);
        document.removeEventListener('keydown', warmAnnouncementAudioOnInteraction);
    });
};

document.addEventListener('pointerdown', warmAnnouncementAudioOnInteraction, { passive: true });
document.addEventListener('keydown', warmAnnouncementAudioOnInteraction);
document.addEventListener('pointerdown', () => {
    if (currentAnnouncementSettings().voice_mode === 'offline_tts') {
        initializeOfflineTts().catch(() => {});
    }
}, { passive: true, once: true });
document.addEventListener('visibilitychange', () => {
    if (document.visibilityState !== 'visible') {
        return;
    }

    syncDisplayVideoSoundState({ attemptUnmute: true });
});

const splitAnnouncementMessage = (message, ticketNumber) => {
    const spokenMessage = String(message ?? '').trim();
    const ticket = String(ticketNumber ?? '').trim();
    const ticketPosition = spokenMessage.toLowerCase().indexOf(ticket.toLowerCase());

    if (! ticket || ticketPosition < 0) {
        return { intro: spokenMessage, ticket: '', outro: '' };
    }

    return {
        intro: spokenMessage.slice(0, ticketPosition).trim().replace(/[,:;-]+$/, ''),
        ticket,
        outro: spokenMessage.slice(ticketPosition + ticket.length).trim().replace(/^[,:;-]+/, '').trim(),
    };
};

const normalizeAnnouncementPayload = (payload) => {
    const data = Array.isArray(payload) ? payload[0] : payload;

    if (! data || typeof data !== 'object') {
        return null;
    }

    return {
        message: data.message ?? '',
        ticket_number: data.ticket_number ?? data.ticketNumber ?? '',
        service_name: data.service_name ?? data.serviceName ?? '',
        service_code: data.service_code ?? data.serviceCode ?? '',
        counter_name: data.counter_name ?? data.counterName ?? '',
        counter_code: data.counter_code ?? data.counterCode ?? '',
        call_id: Number(data.call_id ?? data.callId) || null,
        called_at: data.called_at ?? data.calledAt ?? '',
    };
};

const announcementSpeechText = (announcement, settings) => {
    const { intro, ticket, outro } = splitAnnouncementMessage(
        announcement.message,
        announcement.ticket_number,
    );

    return [intro, ticket ? formatTicketNumberForSpeech(ticket, settings.speak_zero_digits) : '', outro]
        .filter(Boolean)
        .join(', ')
        .replace(/\s+/g, ' ')
        .replace(/\s+([,.!?])/g, '$1')
        .trim();
};

const offlineAnnouncementSegments = (announcement, settings) => {
    const { intro, ticket, outro } = splitAnnouncementMessage(
        announcement.message,
        announcement.ticket_number,
    );
    const segments = [];

    if (intro) {
        segments.push({ text: intro, pauseAfterMs: ticket ? 180 : 0, speedMultiplier: 1 });
    }

    if (ticket) {
        const ticketCharacters = spokenTicketCharacters(ticket, settings.speak_zero_digits);

        ticketCharacters.forEach((character, index) => {
            segments.push({
                text: indonesianDigits[character] ?? indonesianLetters[character] ?? character,
                pauseAfterMs: index === ticketCharacters.length - 1 ? 320 : 240,
                speedMultiplier: 0.82,
            });
        });
    }

    if (outro) {
        segments.push({ text: outro, pauseAfterMs: 0, speedMultiplier: 1 });
    }

    return segments.length > 0
        ? segments
        : [{ text: announcementSpeechText(announcement, settings), pauseAfterMs: 0, speedMultiplier: 1 }];
};

const playCueTransition = async (settings, enabled, audio) => {
    if (! enabled) {
        return;
    }

    await playAnnouncementCue(audio, settings.audio_volume / 100);

    if (settings.cue_gap_ms > 0) {
        await wait(settings.cue_gap_ms);
    }
};

const playOfflineTtsAnnouncement = async (announcement, settings, { includeOpening = true } = {}) => {
    const speechSegments = offlineAnnouncementSegments(announcement, settings);
    const volume = settings.audio_volume / 100;
    const preparedAudio = synthesizeOfflineTts(speechSegments, settings.tts_speed, settings.tts_style)
        .then((blob) => ({ blob, error: null }))
        .catch((error) => ({ blob: null, error }));

    await playCueTransition(settings, includeOpening && settings.opening_enabled, announcementAudioState.openingAudio);

    const { blob, error } = await preparedAudio;

    if (error) {
        throw error;
    }

    if (blob) {
        const source = URL.createObjectURL(blob);
        try {
            await playAnnouncementCue(createAnnouncementAudio(source, volume), volume);
        } finally {
            URL.revokeObjectURL(source);
        }
    }

    if (settings.closing_enabled) {
        await playAnnouncementCue(announcementAudioState.closingAudio, volume);
    }
};

const playOnlineTtsAnnouncement = async (announcement, settings, { includeOpening = true } = {}) => {
    const speechText = announcementSpeechText(announcement, settings);
    const preparedVoice = getOnlineTtsVoices()
        .then((voices) => selectOnlineTtsVoice(voices));

    await playCueTransition(settings, includeOpening && settings.opening_enabled, announcementAudioState.openingAudio);
    await speakOnlineTts(speechText, settings, await preparedVoice);

    if (settings.closing_enabled) {
        await playAnnouncementCue(announcementAudioState.closingAudio, settings.audio_volume / 100);
    }
};

const playAnnouncementAudio = async (detail = {}, settingsOverride = null) => {
    const announcement = normalizeAnnouncementPayload(detail) ?? detail;
    const settings = normalizeAnnouncementSettings(settingsOverride ?? currentAnnouncementSettings());

    if (! settings.announcement_enabled) {
        return;
    }

    for (let repetition = 0; repetition < settings.repeat_count; repetition += 1) {
        if (settings.voice_mode === 'online_tts') {
            try {
                await playOnlineTtsAnnouncement(announcement, settings);
            } catch (error) {
                console.error('TTS online gagal, beralih ke TTS offline lokal.', error);
                await playOfflineTtsAnnouncement(announcement, settings, { includeOpening: false });
            }
        } else {
            try {
                await playOfflineTtsAnnouncement(announcement, settings);
            } catch (error) {
                console.error('TTS offline gagal, beralih ke TTS online browser.', error);
                await playOnlineTtsAnnouncement(announcement, settings, { includeOpening: false });
            }
        }

        if (repetition < settings.repeat_count - 1 && settings.repeat_pause_ms > 0) {
            await wait(settings.repeat_pause_ms);
        }
    }
};

Alpine.data('announcementAudioPreview', () => ({
    playing: false,
    ttsStatus: offlineTtsStatus().status,
    ttsStatusMessage: offlineTtsStatus().message,

    init() {
        this.statusListener = ({ detail }) => {
            this.ttsStatus = detail.status;
            this.ttsStatusMessage = detail.message;
        };
        window.addEventListener('offline-tts-status', this.statusListener);
    },

    destroy() {
        window.removeEventListener('offline-tts-status', this.statusListener);
    },

    settings() {
        const fieldValue = (id, fallback = '') => this.$root.querySelector(`#${id}`)?.value ?? fallback;
        const fieldChecked = (id, fallback = false) => this.$root.querySelector(`#${id}`)?.checked ?? fallback;

        return normalizeAnnouncementSettings({
            voice_mode: fieldChecked('voice-mode-online-tts') ? 'online_tts' : 'offline_tts',
            announcement_enabled: true,
            audio_volume: fieldValue('audio-volume', 100),
            repeat_count: fieldValue('repeat-count', 1),
            opening_enabled: fieldChecked('audio-opening-enabled', true),
            closing_enabled: fieldChecked('audio-closing-enabled', true),
            cue_gap_ms: fieldValue('cue-gap', 0),
            repeat_pause_ms: fieldValue('repeat-pause', 800),
            speak_zero_digits: fieldChecked('speak-zero-digits', true),
            tts_speed: fieldValue('tts-speed', 0.8),
            tts_style: fieldValue('tts-style', 'natural'),
            online_pitch: fieldValue('online-pitch', 1),
        });
    },

    async preview() {
        if (this.playing) {
            return;
        }

        this.playing = true;

        try {
            const settings = this.settings();

            await playAnnouncementAudio({
                message: 'Nomor antrian A001, silakan menuju loket 1.',
                ticket_number: 'A001',
                service_name: 'Pendaftaran',
                service_code: 'A',
                counter_name: 'Loket 1',
                counter_code: '1',
            }, settings);
        } finally {
            this.playing = false;
        }
    },
}));

const configuredVideoVolume = (media, dataAttribute, fallback) => {
    const container = media.closest(`[${dataAttribute}]`);
    const rawValue = Number(container?.getAttribute(dataAttribute) ?? fallback);

    return Math.min(100, Math.max(0, Number.isFinite(rawValue) ? rawValue : fallback));
};

const postVideoCommand = (iframe, func, args = []) => {
    try {
        const targetOrigin = new URL(iframe.src, window.location.href).origin;
        iframe.contentWindow?.postMessage(
            JSON.stringify({ event: 'command', func, args }),
            targetOrigin,
        );
    } catch {
        // Ignore an invalid or unavailable iframe source.
    }
};

const fadeVideoVolume = (video, targetVolume, durationMs) => new Promise((resolve) => {
    const startVolume = video.volume;
    const startedAt = performance.now();

    const step = (timestamp) => {
        const progress = Math.min(1, (timestamp - startedAt) / durationMs);
        video.volume = startVolume + ((targetVolume - startVolume) * progress);

        if (progress < 1) {
            requestAnimationFrame(step);
        } else {
            resolve();
        }
    };

    requestAnimationFrame(step);
});

const syncDisplayVideoSoundState = async ({ attemptUnmute = false, forcePlay = false } = {}) => {
    const playPromises = [];

    document.querySelectorAll('.display-tv-video video').forEach((video) => {
        if (video.closest('[data-prefer-muted="1"]')) {
            video.muted = true;

            return;
        }

        const isDucked = video.dataset.dtvDucked === '1' || window.__displayAnnouncementQueueActive === true;
        const targetVolume = configuredVideoVolume(
            video,
            isDucked ? 'data-video-duck-volume' : 'data-video-volume',
            isDucked ? 4 : 25,
        ) / 100;

        video.volume = targetVolume;

        if (attemptUnmute && window.__displayAudioUnlocked === true) {
            video.muted = false;
            video.dataset.dtvAutoMuted = '0';
        }

        if (forcePlay || (attemptUnmute && window.__displayAudioUnlocked === true)) {
            playPromises.push(video.play()
                .then(() => {
                    video.dataset.dtvAutoMuted = '0';

                    return true;
                })
                .catch(() => {
                    video.muted = true;
                    video.dataset.dtvAutoMuted = '1';

                    return false;
                }));
        }
    });

    document.querySelectorAll('.display-tv-video iframe').forEach((iframe) => {
        if (iframe.dataset.preferMuted === '1') {
            return;
        }

        const isDucked = window.__displayAnnouncementQueueActive === true;
        const targetVolume = configuredVideoVolume(
            iframe,
            isDucked ? 'data-video-duck-volume' : 'data-video-volume',
            isDucked ? 4 : 25,
        );

        postVideoCommand(iframe, 'setVolume', [targetVolume]);

        if (attemptUnmute && window.__displayAudioUnlocked === true) {
            postVideoCommand(iframe, 'unMute');
            postVideoCommand(iframe, 'playVideo');
        }
    });

    return Promise.all(playPromises);
};

const duckDisplayVideo = async () => {
    const fades = [];

    document.querySelectorAll('.display-tv-video video').forEach((video) => {
        if (video.dataset.dtvDucked === '1') {
            return;
        }

        video.dataset.dtvDucked = '1';
        video.dataset.dtvPrevVolume = String(video.volume);
        video.dataset.dtvPrevMuted = video.muted ? '1' : '0';
        const duckVolume = configuredVideoVolume(video, 'data-video-duck-volume', 4) / 100;
        video.volume = duckVolume;

        if (! video.muted) {
            fades.push(fadeVideoVolume(video, duckVolume, 220));
        }
    });

    document.querySelectorAll('.display-tv-video iframe').forEach((iframe) => {
        if (iframe.dataset.preferMuted === '1') {
            return;
        }

        const duckVolume = configuredVideoVolume(iframe, 'data-video-duck-volume', 4);
        postVideoCommand(iframe, 'setVolume', [duckVolume]);
    });

    await Promise.all(fades);
};

const restoreDisplayVideo = async () => {
    const fades = [];

    document.querySelectorAll('.display-tv-video video').forEach((video) => {
        if (video.dataset.dtvDucked !== '1') {
            return;
        }

        const preferMuted = video.closest('[data-prefer-muted="1"]') !== null;
        const wasMuted = video.dataset.dtvPrevMuted === '1' && video.dataset.dtvAutoMuted !== '1';
        const normalVolume = configuredVideoVolume(video, 'data-video-volume', 25) / 100;

        if (preferMuted || wasMuted) {
            video.muted = true;
        } else {
            video.muted = false;
            fades.push(fadeVideoVolume(video, normalVolume, 550));
        }

        delete video.dataset.dtvDucked;
        delete video.dataset.dtvPrevVolume;
        delete video.dataset.dtvPrevMuted;
    });

    document.querySelectorAll('.display-tv-video iframe').forEach((iframe) => {
        if (iframe.dataset.preferMuted === '1') {
            return;
        }

        const normalVolume = configuredVideoVolume(iframe, 'data-video-volume', 25);
        postVideoCommand(iframe, 'unMute');
        postVideoCommand(iframe, 'setVolume', [normalVolume]);
    });

    await Promise.all(fades);
};

const enableDisplayVideoSound = async () => {
    const playResults = await syncDisplayVideoSoundState({ attemptUnmute: true, forcePlay: true });

    if (playResults.some(Boolean)) {
        dispatchDisplayEvent('display-video-sound-enabled');
    }
};

const announcementQueue = [];
const processedCallIds = new Set();
const processedCallOrder = [];
let announcementQueueProcessing = false;
let announcementQueueTimer = null;
let announcementSequence = 0;

const dispatchDisplayEvent = (eventName, detail = {}) => {
    window.dispatchEvent(new CustomEvent(eventName, { detail }));
};

const rememberCallId = (callId) => {
    if (! callId) {
        return;
    }

    const key = String(callId);
    processedCallIds.add(key);
    processedCallOrder.push(key);

    if (processedCallOrder.length > 200) {
        processedCallIds.delete(processedCallOrder.shift());
    }
};

const compareAnnouncements = (left, right) => {
    if (left.call_id && right.call_id && left.call_id !== right.call_id) {
        return left.call_id - right.call_id;
    }

    if (left.called_at && right.called_at && left.called_at !== right.called_at) {
        return String(left.called_at).localeCompare(String(right.called_at));
    }

    return left.sequence - right.sequence;
};

const processAnnouncementQueue = async () => {
    if (announcementQueueProcessing || announcementQueue.length === 0) {
        return;
    }

    if (
        announcementQueue[0].display
        && currentAnnouncementSettings().announcement_enabled
        && window.__displayAudioUnlocked !== true
    ) {
        return;
    }

    announcementQueueProcessing = true;
    window.__displayAnnouncementQueueActive = true;
    let displayAudioWasActive = false;

    try {
        while (announcementQueue.length > 0) {
            const announcement = announcementQueue.shift();
            const announcementSettings = currentAnnouncementSettings();

            if (
                announcement.display
                && announcementSettings.announcement_enabled
                && window.__displayAudioUnlocked !== true
            ) {
                announcementQueue.unshift(announcement);
                break;
            }

            if (announcement.display && announcementSettings.announcement_enabled) {
                displayAudioWasActive = true;
                await duckDisplayVideo();
                dispatchDisplayEvent('display-announcement-started', announcement);
            }

            await playAnnouncementAudio(announcement, announcementSettings);

            if (announcementQueue.length > 0) {
                await wait(800);
            }
        }
    } finally {
        if (displayAudioWasActive) {
            await restoreDisplayVideo();
        }

        announcementQueueProcessing = false;

        if (announcementQueue.length === 0) {
            window.__displayAnnouncementQueueActive = false;
            dispatchDisplayEvent('display-announcement-queue-idle');
        }
    }
};

const scheduleAnnouncementQueue = () => {
    clearTimeout(announcementQueueTimer);
    announcementQueueTimer = setTimeout(() => processAnnouncementQueue(), 260);
};

const enqueueAnnouncement = (payload, { display = false } = {}) => {
    const announcement = normalizeAnnouncementPayload(payload);
    const playbackEnabled = currentAnnouncementSettings().announcement_enabled;

    if (! announcement?.message) {
        return;
    }

    const callKey = announcement.call_id ? String(announcement.call_id) : null;

    if (callKey && processedCallIds.has(callKey)) {
        return;
    }

    rememberCallId(announcement.call_id);
    announcementQueue.push({
        ...announcement,
        display,
        sequence: ++announcementSequence,
    });
    announcementQueue.sort(compareAnnouncements);

    if (display && playbackEnabled) {
        window.__displayAnnouncementQueueActive = true;

        if (window.__displayAudioUnlocked !== true) {
            const latestDisplayAnnouncement = announcementQueue
                .filter((queuedAnnouncement) => queuedAnnouncement.display)
                .at(-1);

            if (latestDisplayAnnouncement) {
                dispatchDisplayEvent('display-announcement-started', latestDisplayAnnouncement);
            }
        }
    }

    if (! display || ! playbackEnabled || window.__displayAudioUnlocked === true) {
        scheduleAnnouncementQueue();
    }
};

Alpine.data('displayTvNowServing', (initialCall = null) => ({
    currentCall: normalizeAnnouncementPayload(initialCall),
    pendingCall: null,
    hasPendingSync: false,

    showCall(call) {
        this.currentCall = normalizeAnnouncementPayload(call);
    },

    syncCall(call) {
        const syncedCall = normalizeAnnouncementPayload(call);

        if (
            syncedCall?.call_id
            && this.currentCall?.call_id
            && syncedCall.call_id < this.currentCall.call_id
        ) {
            return;
        }

        this.pendingCall = syncedCall;
        this.hasPendingSync = true;

        if (! window.__displayAnnouncementQueueActive) {
            this.applyPendingCall();
        }
    },

    finishQueue() {
        this.applyPendingCall();
    },

    applyPendingCall() {
        if (! this.hasPendingSync) {
            return;
        }

        this.currentCall = this.pendingCall;
        this.pendingCall = null;
        this.hasPendingSync = false;
    },

    ticketCode() {
        if (! this.currentCall) {
            return '-';
        }

        return String(this.currentCall.service_code || this.currentCall.ticket_number.match(/^[A-Z]+/i)?.[0] || '-')
            .toUpperCase();
    },

    ticketNumber() {
        if (! this.currentCall) {
            return '---';
        }

        const ticketNumber = String(this.currentCall.ticket_number || '');
        const serviceCode = this.ticketCode();

        return ticketNumber.toUpperCase().startsWith(serviceCode)
            ? ticketNumber.slice(serviceCode.length)
            : ticketNumber.replace(/^[A-Z]+/i, '');
    },
}));

Alpine.data('displayTvShell', () => ({
    unlocked: window.__displayAudioUnlocked === true,
    focusSyncHandler: null,

    init() {
        document.querySelectorAll('.display-tv-video iframe').forEach((iframe) => {
            const syncVolume = () => {
                if (iframe.dataset.preferMuted === '1') {
                    return;
                }

                const isDucked = window.__displayAnnouncementQueueActive === true;
                const targetVolume = configuredVideoVolume(
                    iframe,
                    isDucked ? 'data-video-duck-volume' : 'data-video-volume',
                    isDucked ? 4 : 25,
                );

                postVideoCommand(iframe, 'setVolume', [targetVolume]);

                if (this.unlocked) {
                    postVideoCommand(iframe, 'unMute');
                }
            };

            iframe.addEventListener('load', () => setTimeout(syncVolume, 250), { once: true });
            setTimeout(syncVolume, 1000);
        });

        this.focusSyncHandler = () => syncDisplayVideoSoundState({ attemptUnmute: this.unlocked });
        setTimeout(this.focusSyncHandler, 250);
        window.addEventListener('focus', this.focusSyncHandler);

        if (displayKioskAutostartEnabled) {
            this.unlock();
        }
    },

    async unlock() {
        this.unlocked = true;
        window.__displayAudioUnlocked = true;
        sessionStorage.setItem('display-audio-unlocked', '1');
        const videoSoundPromise = enableDisplayVideoSound();
        const audioWarmPromise = warmAnnouncementAudio();

        const ttsWarmPromise = currentAnnouncementSettings().voice_mode === 'offline_tts'
            ? initializeOfflineTts().catch(() => false)
            : getOnlineTtsVoices().catch(() => []);

        await Promise.all([videoSoundPromise, audioWarmPromise, ttsWarmPromise]);
        scheduleAnnouncementQueue();
    },

    destroy() {
        if (this.focusSyncHandler) {
            window.removeEventListener('focus', this.focusSyncHandler);
        }
    },
}));

Alpine.data('displayTvVideo', (preferMuted = false, mediaUrls = [], normalVolume = 25) => ({
    preferMuted,
    normalVolume: Math.min(
        100,
        Math.max(0, Number.isFinite(Number(normalVolume)) ? Number(normalVolume) : 25),
    ),
    needsInteraction: false,
    mediaUrls: Array.isArray(mediaUrls) ? mediaUrls.filter(Boolean) : [],
    currentIndex: 0,

    async boot() {
        const video = this.$refs.video;

        if (! video || this.mediaUrls.length === 0) {
            return;
        }

        video.src = this.mediaUrls[this.currentIndex];
        video.loop = false;
        const isDucked = video.dataset.dtvDucked === '1';
        video.volume = configuredVideoVolume(
            video,
            isDucked ? 'data-video-duck-volume' : 'data-video-volume',
            isDucked ? 4 : this.normalVolume,
        ) / 100;
        video.muted = this.preferMuted || (isDucked && video.dataset.dtvPrevMuted === '1');

        try {
            await video.play();
            video.dataset.dtvAutoMuted = video.muted ? '1' : '0';
            this.needsInteraction = false;
        } catch {
            if (! this.preferMuted) {
                video.muted = true;
                video.dataset.dtvAutoMuted = '1';
                this.needsInteraction = true;

                try {
                    await video.play();
                } catch {
                    // Browser blocked autoplay entirely.
                }
            }
        }
    },

    async onEnded() {
        this.currentIndex = this.mediaUrls.length > 1
            ? (this.currentIndex + 1) % this.mediaUrls.length
            : 0;

        await this.boot();
    },

    async tryUnmute() {
        const video = this.$refs.video;

        if (! video || this.preferMuted) {
            return;
        }

        const isDucked = video.dataset.dtvDucked === '1';
        video.muted = false;
        video.volume = configuredVideoVolume(
            video,
            isDucked ? 'data-video-duck-volume' : 'data-video-volume',
            isDucked ? 4 : this.normalVolume,
        ) / 100;

        if (isDucked) {
            video.dataset.dtvPrevMuted = '0';
            video.dataset.dtvPrevVolume = String(this.normalVolume / 100);
        }

        try {
            await video.play();
            video.dataset.dtvAutoMuted = '0';
            this.needsInteraction = false;
        } catch {
            video.muted = true;
            video.dataset.dtvAutoMuted = '1';
            this.needsInteraction = true;
        }
    },
}));

Alpine.data('navaLayout', () => ({
    sidebarMobileOpen: false,
    sidebarCollapsed: false,
    darkMode: JSON.parse(localStorage.getItem('darkMode') ?? 'false'),

    init() {
        this.$watch('darkMode', (value) => {
            localStorage.setItem('darkMode', JSON.stringify(value));
        });

        this.$watch('sidebarMobileOpen', (open) => {
            const isMobile = window.matchMedia('(max-width: 1279px)').matches;
            document.body.classList.toggle('overflow-hidden', open && isMobile);
        });
    },

    toggleSidebar() {
        if (window.matchMedia('(min-width: 1280px)').matches) {
            this.sidebarCollapsed = ! this.sidebarCollapsed;
        } else {
            this.sidebarMobileOpen = ! this.sidebarMobileOpen;
        }
    },

    closeMobileSidebar() {
        this.sidebarMobileOpen = false;
    },
}));

const displayAnnouncementListenerState = window.__queueDisplayAnnouncementListenerState ??= {
    handler: null,
};

if (displayAnnouncementListenerState.handler) {
    window.removeEventListener('queue-display-announcement', displayAnnouncementListenerState.handler);
}

displayAnnouncementListenerState.handler = (event) => {
    enqueueAnnouncement(event.detail, { display: true });
};

window.addEventListener('queue-display-announcement', displayAnnouncementListenerState.handler);

Livewire.start();
