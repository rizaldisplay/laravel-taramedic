import createPiperPhonemize from '@diffusionstudio/piper-wasm';
import ort from 'onnxruntime-web/wasm';

const MODEL_BASE = '/tts/models/id_ID-news_tts-medium/id_ID-news_tts-medium.onnx';
const PIPER_RUNTIME_BASE = '/tts/runtime/piper/piper_phonemize';

let modelConfig = null;
let inferenceSession = null;
let initializationPromise = null;

const postStatus = (status, detail = '') => {
    self.postMessage({ type: 'status', status, detail });
};

const initialize = async () => {
    if (inferenceSession && modelConfig) {
        return;
    }

    if (initializationPromise) {
        return initializationPromise;
    }

    initializationPromise = (async () => {
        postStatus('loading', 'Memuat model suara Bahasa Indonesia...');

        ort.env.wasm.numThreads = 1;
        ort.env.wasm.simd = true;
        ort.env.wasm.wasmPaths = '/tts/runtime/onnx/';

        const [configResponse, modelResponse] = await Promise.all([
            fetch(`${MODEL_BASE}.json`, { cache: 'force-cache' }),
            fetch(MODEL_BASE, { cache: 'force-cache' }),
        ]);

        if (! configResponse.ok || ! modelResponse.ok) {
            throw new Error('Model suara Bahasa Indonesia tidak ditemukan di folder public/tts.');
        }

        modelConfig = await configResponse.json();
        inferenceSession = await ort.InferenceSession.create(
            await modelResponse.arrayBuffer(),
            { executionProviders: ['wasm'] },
        );

        postStatus('ready', 'TTS offline Bahasa Indonesia siap.');
    })().catch((error) => {
        initializationPromise = null;
        inferenceSession = null;
        modelConfig = null;
        throw error;
    });

    return initializationPromise;
};

const phonemeIdsForSegments = async (texts) => new Promise(async (resolve, reject) => {
    let settled = false;
    const phonemeGroups = [];

    try {
        const module = await createPiperPhonemize({
            print: (data) => {
                if (settled) {
                    return;
                }

                try {
                    const parsed = JSON.parse(data);
                    const outputs = Array.isArray(parsed) ? parsed : [parsed];

                    outputs.forEach((output) => {
                        if (Array.isArray(output?.phoneme_ids)) {
                            phonemeGroups.push(output.phoneme_ids);
                        }
                    });

                    if (phonemeGroups.length >= texts.length) {
                        settled = true;
                        resolve(phonemeGroups.slice(0, texts.length));
                    }
                } catch (error) {
                    settled = true;
                    reject(error);
                }
            },
            printErr: (message) => {
                if (! settled) {
                    settled = true;
                    reject(new Error(message));
                }
            },
            locateFile: (url) => {
                if (url.endsWith('.wasm')) {
                    return `${PIPER_RUNTIME_BASE}.wasm`;
                }

                if (url.endsWith('.data')) {
                    return `${PIPER_RUNTIME_BASE}.data`;
                }

                return url;
            },
        });

        module.callMain([
            '-l',
            modelConfig.espeak.voice,
            '--input',
            JSON.stringify(texts.map((text) => ({ text: text.trim() }))),
            '--espeak_data',
            '/espeak-ng-data',
        ]);

        if (! settled) {
            settled = true;
            reject(new Error('Phonemizer tidak menghasilkan seluruh segmen ucapan.'));
        }
    } catch (error) {
        if (! settled) {
            settled = true;
            reject(error);
        }
    }
});

const voiceStyleScales = (style, inference) => {
    if (style === 'calm') {
        return { noiseScale: 0.42, noiseW: 0.45, lengthMultiplier: 1.08 };
    }

    if (style === 'expressive') {
        return { noiseScale: 0.92, noiseW: 1.08, lengthMultiplier: 0.96 };
    }

    return {
        noiseScale: inference.noise_scale,
        noiseW: inference.noise_w,
        lengthMultiplier: 1,
    };
};

const softenEdges = (pcm, sampleRate) => {
    const fadeSamples = Math.min(Math.round(sampleRate * 0.006), Math.floor(pcm.length / 2));

    for (let index = 0; index < fadeSamples; index += 1) {
        const gain = index / fadeSamples;
        pcm[index] *= gain;
        pcm[pcm.length - index - 1] *= gain;
    }

    return pcm;
};

const mergeAudioSegments = (audioSegments, sampleRate) => {
    const preampGain = 1.55;
    const outputPeak = 0.98;
    const totalSamples = audioSegments.reduce(
        (total, segment) => total + segment.pcm.length + Math.round(sampleRate * segment.pauseAfterMs / 1000),
        0,
    );
    const merged = new Float32Array(totalSamples);
    let offset = 0;

    audioSegments.forEach((segment) => {
        merged.set(softenEdges(segment.pcm, sampleRate), offset);
        offset += segment.pcm.length + Math.round(sampleRate * segment.pauseAfterMs / 1000);
    });

    let peak = 0;
    merged.forEach((sample) => {
        peak = Math.max(peak, Math.abs(sample));
    });

    if (peak > 0) {
        const normalizationGain = Math.min(1.5, 1 / peak);
        const limiterCeiling = Math.tanh(preampGain);

        for (let index = 0; index < merged.length; index += 1) {
            const normalizedSample = merged[index] * normalizationGain;

            merged[index] = outputPeak * Math.tanh(normalizedSample * preampGain) / limiterCeiling;
        }
    }

    return merged;
};

const normalizeSegments = (segments) => (Array.isArray(segments) ? segments : [])
    .map((segment) => ({
        text: String(segment?.text || '').trim(),
        pauseAfterMs: Math.max(0, Math.min(1000, Number(segment?.pauseAfterMs) || 0)),
        speedMultiplier: Math.max(0.7, Math.min(1.1, Number(segment?.speedMultiplier) || 1)),
    }))
    .filter((segment) => segment.text !== '');

const synthesize = async (rawSegments, speed = 0.8, style = 'natural') => {
    await initialize();

    const segments = normalizeSegments(rawSegments);

    if (segments.length === 0) {
        throw new Error('Teks panggilan TTS offline kosong.');
    }

    const phonemeGroups = await phonemeIdsForSegments(segments.map((segment) => segment.text));
    const inference = modelConfig.inference;
    const { noiseScale, noiseW, lengthMultiplier } = voiceStyleScales(style, inference);
    const boundedSpeed = Math.max(0.65, Math.min(1, Number(speed) || 0.8));
    const audioSegments = [];

    for (let index = 0; index < segments.length; index += 1) {
        const segment = segments[index];
        const phonemeIds = phonemeGroups[index];
        const feeds = {
            input: new ort.Tensor('int64', phonemeIds, [1, phonemeIds.length]),
            input_lengths: new ort.Tensor('int64', [phonemeIds.length]),
            scales: new ort.Tensor('float32', [
                noiseScale,
                (inference.length_scale * 1.12 * lengthMultiplier)
                    / (boundedSpeed * segment.speedMultiplier),
                noiseW,
            ]),
        };
        const result = await inferenceSession.run(feeds);

        audioSegments.push({
            pcm: new Float32Array(result.output.data),
            pauseAfterMs: segment.pauseAfterMs,
        });
    }

    return {
        pcm: mergeAudioSegments(audioSegments, modelConfig.audio.sample_rate),
        sampleRate: modelConfig.audio.sample_rate,
    };
};

self.addEventListener('message', async ({ data }) => {
    const requestId = data?.requestId;

    try {
        if (data?.type === 'initialize') {
            await initialize();
            self.postMessage({ type: 'initialized', requestId });

            return;
        }

        if (data?.type !== 'synthesize') {
            return;
        }

        postStatus('synthesizing', 'Membuat audio panggilan secara lokal...');
        const { pcm, sampleRate } = await synthesize(data.segments, data.speed, data.style);

        self.postMessage(
            { type: 'audio', requestId, pcm: pcm.buffer, sampleRate },
            [pcm.buffer],
        );
        postStatus('ready', 'TTS offline Bahasa Indonesia siap.');
    } catch (error) {
        const message = error instanceof Error ? error.message : String(error);
        self.postMessage({ type: 'error', requestId, message });
        postStatus('error', message);
    }
});
