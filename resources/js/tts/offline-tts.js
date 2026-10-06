const worker = new Worker(new URL('./offline-tts-worker.js', import.meta.url), { type: 'module' });
const pendingRequests = new Map();
const pendingSyntheses = new Map();
const synthesisCache = new Map();
const MAX_CACHED_UTTERANCES = 8;
let requestSequence = 0;
let status = 'idle';
let statusDetail = 'TTS offline belum dimuat.';

const wavBlob = (pcm, sampleRate) => {
    const bytesPerSample = 2;
    const buffer = new ArrayBuffer(44 + (pcm.length * bytesPerSample));
    const view = new DataView(buffer);
    const writeText = (offset, text) => {
        for (let index = 0; index < text.length; index += 1) {
            view.setUint8(offset + index, text.charCodeAt(index));
        }
    };

    writeText(0, 'RIFF');
    view.setUint32(4, 36 + (pcm.length * bytesPerSample), true);
    writeText(8, 'WAVE');
    writeText(12, 'fmt ');
    view.setUint32(16, 16, true);
    view.setUint16(20, 1, true);
    view.setUint16(22, 1, true);
    view.setUint32(24, sampleRate, true);
    view.setUint32(28, sampleRate * bytesPerSample, true);
    view.setUint16(32, bytesPerSample, true);
    view.setUint16(34, 16, true);
    writeText(36, 'data');
    view.setUint32(40, pcm.length * bytesPerSample, true);

    for (let index = 0; index < pcm.length; index += 1) {
        const sample = Math.max(-1, Math.min(1, pcm[index]));
        view.setInt16(44 + (index * bytesPerSample), sample < 0 ? sample * 32768 : sample * 32767, true);
    }

    return new Blob([buffer], { type: 'audio/wav' });
};

worker.addEventListener('message', ({ data }) => {
    if (data?.type === 'status') {
        status = data.status;
        statusDetail = data.detail;
        window.dispatchEvent(new CustomEvent('offline-tts-status', { detail: { status, message: statusDetail } }));

        return;
    }

    const request = pendingRequests.get(data?.requestId);

    if (! request) {
        return;
    }

    pendingRequests.delete(data.requestId);
    clearTimeout(request.timeoutId);

    if (data.type === 'error') {
        request.reject(new Error(data.message));

        return;
    }

    if (data.type === 'audio') {
        request.resolve(wavBlob(new Float32Array(data.pcm), data.sampleRate));

        return;
    }

    request.resolve(true);
});

const request = (type, payload = {}) => new Promise((resolve, reject) => {
    const requestId = ++requestSequence;
    const timeoutId = setTimeout(() => {
        pendingRequests.delete(requestId);
        reject(new Error('TTS offline melewati batas waktu 90 detik.'));
    }, 90000);

    pendingRequests.set(requestId, { resolve, reject, timeoutId });
    worker.postMessage({ type, requestId, ...payload });
});

export const initializeOfflineTts = () => request('initialize');

export const synthesizeOfflineTts = (segments, speed = 0.8, style = 'natural') => {
    const serializedSegments = JSON.stringify(segments);
    const cacheKey = `id_ID-news_tts-medium|${speed}|${style}|${serializedSegments}`;

    if (synthesisCache.has(cacheKey)) {
        const cachedBlob = synthesisCache.get(cacheKey);
        synthesisCache.delete(cacheKey);
        synthesisCache.set(cacheKey, cachedBlob);

        return Promise.resolve(cachedBlob);
    }

    if (pendingSyntheses.has(cacheKey)) {
        return pendingSyntheses.get(cacheKey);
    }

    const synthesisPromise = request('synthesize', { segments, speed, style })
        .then((blob) => {
            synthesisCache.set(cacheKey, blob);

            while (synthesisCache.size > MAX_CACHED_UTTERANCES) {
                synthesisCache.delete(synthesisCache.keys().next().value);
            }

            return blob;
        })
        .finally(() => pendingSyntheses.delete(cacheKey));

    pendingSyntheses.set(cacheKey, synthesisPromise);

    return synthesisPromise;
};

export const offlineTtsStatus = () => ({ status, message: statusDetail });
