<?php

it('ships the Indonesian model and every offline TTS runtime asset', function () {
    $assets = [
        'models/id_ID-news_tts-medium/id_ID-news_tts-medium.onnx' => 'ed8f02aa593f7af6b19acbdb8142e0da0dd72f46194eb33d38e0eb10a52597e8',
        'models/id_ID-news_tts-medium/id_ID-news_tts-medium.onnx.json' => '1ef677072668a5e172e0759b1d3871f129009d1167f093325a17607f7add5ad7',
        'runtime/onnx/ort-wasm-simd.wasm' => 'f533b5f21790563d7556c611c3835ac65848ee326da2689aac54ed7d9cc8c6a4',
        'runtime/piper/piper_phonemize.data' => '29f1025eb23a5b5c192cd14a6efbce4509402ff265405072ee6f7d1a09b78f8c',
        'runtime/piper/piper_phonemize.wasm' => 'b777cd107a91d2bcc6a1ea46f2c26a662a7407394fe84589198aeaa83dd7a9d6',
    ];

    foreach ($assets as $relativePath => $expectedHash) {
        $path = public_path("tts/{$relativePath}");

        expect($path)->toBeFile()
            ->and(hash_file('sha256', $path))->toBe($expectedHash);
    }
});

it('uses only project-local URLs for Indonesian speech inference', function () {
    $worker = file_get_contents(resource_path('js/tts/offline-tts-worker.js'));

    expect($worker)
        ->toContain("'/tts/models/id_ID-news_tts-medium/id_ID-news_tts-medium.onnx'")
        ->toContain("'/tts/runtime/piper/piper_phonemize'")
        ->toContain("ort.env.wasm.wasmPaths = '/tts/runtime/onnx/'")
        ->toContain('ort.env.wasm.numThreads = 1')
        ->not->toContain('https://')
        ->not->toContain('cdn.');
});

it('prepares local speech during the opening cue and caches repeated sentences', function () {
    $player = file_get_contents(resource_path('js/app.js'));
    $client = file_get_contents(resource_path('js/tts/offline-tts.js'));

    expect($player)
        ->toContain('const preparedAudio = synthesizeOfflineTts')
        ->toContain('await playCueTransition(settings, includeOpening && settings.opening_enabled')
        ->toContain('const { blob, error } = await preparedAudio')
        ->and(strpos($player, 'const preparedAudio = synthesizeOfflineTts'))
        ->toBeLessThan(strpos($player, 'await playCueTransition(settings, includeOpening && settings.opening_enabled'));

    expect($client)
        ->toContain('const synthesisCache = new Map()')
        ->toContain('const pendingSyntheses = new Map()')
        ->toContain('MAX_CACHED_UTTERANCES = 8');
});

it('renders configurable ticket characters as louder slower segments with measured pauses', function () {
    $player = file_get_contents(resource_path('js/app.js'));
    $pronunciation = file_get_contents(resource_path('js/tts/ticket-pronunciation.js'));
    $worker = file_get_contents(resource_path('js/tts/offline-tts-worker.js'));

    expect($player)
        ->toContain('const offlineAnnouncementSegments = (announcement, settings) =>')
        ->toContain('spokenTicketCharacters(ticket, settings.speak_zero_digits)')
        ->toContain('speedMultiplier: 0.82')
        ->toContain('pauseAfterMs: index === ticketCharacters.length - 1 ? 320 : 240');

    expect($pronunciation)
        ->toContain(".filter((character) => speakZeroDigits || character !== '0')")
        ->toContain("0: 'kosong'");

    expect($worker)
        ->toContain('const mergeAudioSegments = (audioSegments, sampleRate) =>')
        ->toContain('inference.length_scale * 1.12 * lengthMultiplier')
        ->toContain('const preampGain = 1.55')
        ->toContain('const outputPeak = 0.98')
        ->toContain('Math.tanh(normalizedSample * preampGain) / limiterCeiling')
        ->toContain('noiseScale: 0.42, noiseW: 0.45, lengthMultiplier: 1.08')
        ->toContain('noiseScale: 0.92, noiseW: 1.08, lengthMultiplier: 0.96');
});

it('contains no runtime dependency on legacy letter number or phrase fragments', function () {
    $player = file_get_contents(resource_path('js/app.js'));

    expect($player)
        ->not->toContain('/sounds/antrian.wav')
        ->not->toContain('/sounds/counter.wav')
        ->not->toContain('identifierAudioFiles')
        ->not->toContain('naturalNumberAudioFiles');
});
