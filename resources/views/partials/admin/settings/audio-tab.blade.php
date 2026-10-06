<div class="max-w-5xl">
    <div class="mb-6">
        <h2 class="font-display text-lg font-bold tracking-[-.025em] text-ink-950 dark:text-white">Pengaturan Suara Panggilan</h2>
        <p class="mt-1 text-sm text-gray-500">Gunakan TTS Indonesia lokal tanpa internet atau TTS online dari browser dan sistem operasi.</p>
    </div>

    <form wire:submit="saveAudio" class="space-y-7" x-data="announcementAudioPreview()">
        <section class="rounded-2xl border border-brand-200 bg-brand-50/70 p-4 dark:border-brand-500/30 dark:bg-brand-500/10">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h3 class="text-sm font-bold text-brand-950 dark:text-brand-100">Status pemanggilan</h3>
                    <p class="mt-1 text-sm text-brand-800 dark:text-brand-200">Suara hanya diputar pada halaman display setelah tombol “Aktifkan suara TV” ditekan.</p>
                </div>
                <label class="flex shrink-0 cursor-pointer items-center gap-3 rounded-xl bg-white px-3.5 py-2.5 text-sm font-bold text-ink-800 shadow-sm dark:bg-white/8 dark:text-white">
                    <input id="announcement-enabled" type="checkbox" wire:model="audio.announcement_enabled" class="rounded border-ink-300 text-brand-600 focus:ring-brand-500">
                    Aktifkan panggilan
                </label>
            </div>
        </section>

        <section>
            <h3 class="text-sm font-bold text-ink-900 dark:text-white">Mode Text-to-Speech</h3>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <label class="cursor-pointer rounded-2xl border border-ink-200 bg-white p-4 has-checked:border-brand-500 has-checked:ring-2 has-checked:ring-brand-500/15 dark:border-white/10 dark:bg-white/3">
                    <span class="flex items-start gap-3">
                        <input id="voice-mode-offline-tts" type="radio" value="offline_tts" wire:model.live="audio.voice_mode" class="mt-1 border-ink-300 text-brand-600 focus:ring-brand-500">
                        <span>
                            <span class="block text-sm font-bold text-ink-950 dark:text-white">TTS Offline Lokal</span>
                            <span class="mt-1 block text-xs leading-5 text-ink-500 dark:text-ink-300">Model Piper Bahasa Indonesia berjalan di perangkat display. Tidak memakai API, CDN, atau internet.</span>
                        </span>
                    </span>
                </label>
                <label class="cursor-pointer rounded-2xl border border-ink-200 bg-white p-4 has-checked:border-brand-500 has-checked:ring-2 has-checked:ring-brand-500/15 dark:border-white/10 dark:bg-white/3">
                    <span class="flex items-start gap-3">
                        <input id="voice-mode-online-tts" type="radio" value="online_tts" wire:model.live="audio.voice_mode" class="mt-1 border-ink-300 text-brand-600 focus:ring-brand-500">
                        <span>
                            <span class="block text-sm font-bold text-ink-950 dark:text-white">TTS Online Browser</span>
                            <span class="mt-1 block text-xs leading-5 text-ink-500 dark:text-ink-300">Memilih voice Indonesia terbaik dari browser/OS. Kualitas dan kebutuhan internet mengikuti voice pada perangkat.</span>
                        </span>
                    </span>
                </label>
            </div>
            @error('audio.voice_mode') <p class="admin-form-error">{{ $message }}</p> @enderror
        </section>

        <section x-show="$wire.audio.voice_mode === 'offline_tts'" x-cloak class="space-y-5">
            <div>
                <h3 class="text-sm font-bold text-ink-900 dark:text-white">Karakter suara offline</h3>
                <p class="mt-1 text-xs text-ink-500 dark:text-ink-300">Voice <code>id_ID-news_tts-medium</code> diproses lokal melalui ONNX WebAssembly.</p>
            </div>
            <div class="rounded-xl border border-ink-200 bg-ink-50/70 px-4 py-3 dark:border-white/8 dark:bg-white/3">
                <div class="flex items-start gap-3">
                    <span class="mt-1 size-2 shrink-0 rounded-full" :class="ttsStatus === 'ready' ? 'bg-emerald-500' : (ttsStatus === 'error' ? 'bg-red-500' : 'bg-amber-500')"></span>
                    <div>
                        <p class="text-sm font-semibold text-ink-800 dark:text-ink-100" x-text="ttsStatusMessage"></p>
                        <p class="mt-1 text-xs leading-5 text-ink-500 dark:text-ink-300">Model dipanaskan saat suara TV diaktifkan. Hasil kalimat yang sama disimpan sementara agar pengulangan tidak menyintesis ulang.</p>
                    </div>
                </div>
            </div>
            <div>
                <label for="tts-style" class="admin-form-label">Karakter intonasi lokal</label>
                <select id="tts-style" wire:model="audio.tts_style" class="admin-form-input">
                    <option value="calm">Tenang — stabil dan lebih formal</option>
                    <option value="natural">Natural — variasi bawaan model</option>
                    <option value="expressive">Ekspresif — intonasi dan durasi lebih bervariasi</option>
                </select>
                <p class="admin-form-hint">Preset kini membedakan kestabilan, variasi fonem, dan tempo. Identitas suara tetap sama karena model lokal hanya memiliki satu pembicara.</p>
                @error('audio.tts_style') <p class="admin-form-error">{{ $message }}</p> @enderror
            </div>
        </section>

        <section x-show="$wire.audio.voice_mode === 'online_tts'" x-cloak class="space-y-5">
            <div class="rounded-xl border border-sky-200 bg-sky-50/70 px-4 py-3 dark:border-sky-500/25 dark:bg-sky-500/10">
                <p class="text-sm font-semibold text-sky-950 dark:text-sky-100">Pemilihan voice otomatis</p>
                <p class="mt-1 text-xs leading-5 text-sky-800 dark:text-sky-200">Sistem memprioritaskan voice <code>id-ID</code>, lalu voice Melayu, dan terakhir voice default browser. Voice neural/online mendapat prioritas tertinggi.</p>
            </div>
            <div>
                <label for="online-pitch" class="admin-form-label">Nada suara online</label>
                <select id="online-pitch" wire:model="audio.online_pitch" class="admin-form-input">
                    <option value="0.85">Rendah (0,85×)</option>
                    <option value="0.95">Agak rendah (0,95×)</option>
                    <option value="1">Natural (1×)</option>
                    <option value="1.05">Agak tinggi (1,05×)</option>
                    <option value="1.15">Tinggi (1,15×)</option>
                </select>
                @error('audio.online_pitch') <p class="admin-form-error">{{ $message }}</p> @enderror
            </div>
        </section>

        <section class="space-y-5">
            <div>
                <h3 class="text-sm font-bold text-ink-900 dark:text-white">Kalimat dan pelafalan</h3>
                <p class="mt-1 text-xs text-ink-500 dark:text-ink-300" x-text="$wire.audio.speak_zero_digits ? 'A001 dibaca: a, kosong, kosong, satu.' : 'A001 dibaca: a, satu. Angka 0 dilewati.'"></p>
            </div>
            <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-ink-200 bg-ink-50/70 px-3.5 py-3 dark:border-white/8 dark:bg-white/3">
                <input id="speak-zero-digits" type="checkbox" wire:model.live="audio.speak_zero_digits" class="mt-0.5 rounded border-ink-300 text-brand-600 focus:ring-brand-500">
                <span>
                    <span class="block text-sm font-semibold text-ink-800 dark:text-ink-100">Sebutkan angka 0</span>
                    <span class="mt-1 block text-xs leading-5 text-ink-500 dark:text-ink-300">Jika dimatikan, semua digit 0 pada nomor antrean dilewati saat TTS offline maupun online.</span>
                </span>
            </label>
            @error('audio.speak_zero_digits') <p class="admin-form-error">{{ $message }}</p> @enderror
            <div class="grid gap-5 md:grid-cols-[14rem_minmax(0,1fr)]">
                <div>
                    <label for="tts-speed" class="admin-form-label">Kecepatan suara</label>
                    <select id="tts-speed" wire:model="audio.tts_speed" class="admin-form-input">
                        <option value="0.65">Sangat lambat dan tegas</option>
                        <option value="0.72">Lambat dan jelas</option>
                        <option value="0.8">Normal dan jelas</option>
                        <option value="0.9">Sedikit lebih cepat</option>
                    </select>
                    <p class="admin-form-hint">Kecepatan telah dikalibrasi khusus untuk voice Indonesia lokal.</p>
                    @error('audio.tts_speed') <p class="admin-form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="tts-template" class="admin-form-label">Kalimat pengumuman</label>
                    <textarea id="tts-template" wire:model="audio.tts_template" rows="3" class="admin-form-input font-mono text-sm" placeholder="Nomor antrian {ticket_number}, silakan menuju loket {counter_name}"></textarea>
                    <p class="admin-form-hint">Placeholder: {ticket_number}, {service_name}, {counter_name}, {counter_code}</p>
                    @error('audio.tts_template') <p class="admin-form-error">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        <section>
            <h3 class="text-sm font-bold text-ink-900 dark:text-white">Nada, volume, dan tempo</h3>
            <div class="mt-4 grid gap-5 md:grid-cols-2">
                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-ink-200 bg-ink-50/70 px-3.5 py-3 dark:border-white/8 dark:bg-white/3">
                    <input id="audio-opening-enabled" type="checkbox" wire:model="audio.opening_enabled" class="mt-0.5 rounded border-ink-300 text-brand-600 focus:ring-brand-500">
                    <span class="text-sm font-semibold text-ink-800 dark:text-ink-100">Putar nada pembuka</span>
                </label>
                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-ink-200 bg-ink-50/70 px-3.5 py-3 dark:border-white/8 dark:bg-white/3">
                    <input id="audio-closing-enabled" type="checkbox" wire:model="audio.closing_enabled" class="mt-0.5 rounded border-ink-300 text-brand-600 focus:ring-brand-500">
                    <span class="text-sm font-semibold text-ink-800 dark:text-ink-100">Putar nada penutup</span>
                </label>
                <div>
                    <div class="flex items-center justify-between gap-3">
                        <label for="audio-volume" class="admin-form-label">Volume panggilan</label>
                        <span class="text-xs font-bold text-brand-700 dark:text-brand-300" x-text="`${$wire.audio.audio_volume}%`"></span>
                    </div>
                    <input id="audio-volume" type="range" min="0" max="100" step="5" wire:model.live="audio.audio_volume" class="w-full accent-brand-600">
                    @error('audio.audio_volume') <p class="admin-form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="repeat-count" class="admin-form-label">Jumlah pengulangan</label>
                    <select id="repeat-count" wire:model="audio.repeat_count" class="admin-form-input">
                        <option value="1">1 kali</option>
                        <option value="2">2 kali</option>
                        <option value="3">3 kali</option>
                    </select>
                    @error('audio.repeat_count') <p class="admin-form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="cue-gap" class="admin-form-label">Jeda setelah nada pembuka</label>
                    <input id="cue-gap" type="number" min="0" max="500" step="25" wire:model="audio.cue_gap_ms" class="admin-form-input">
                    <p class="admin-form-hint">Milidetik. Gunakan 0 agar suara langsung dimulai setelah opening.</p>
                    @error('audio.cue_gap_ms') <p class="admin-form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="repeat-pause" class="admin-form-label">Jeda antar pengulangan</label>
                    <input id="repeat-pause" type="number" min="0" max="5000" step="100" wire:model="audio.repeat_pause_ms" class="admin-form-input">
                    @error('audio.repeat_pause_ms') <p class="admin-form-error">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        <div class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-6 sm:flex-row sm:items-center sm:justify-end dark:border-gray-800">
            <button type="button" class="admin-btn-secondary" @click="preview()" :disabled="playing">
                <span x-show="! playing">Uji suara A001 · Loket 1</span>
                <span x-show="playing" x-cloak x-text="$wire.audio.voice_mode === 'offline_tts' && (ttsStatus === 'loading' || ttsStatus === 'synthesizing') ? ttsStatusMessage : 'Sedang memutar...'"></span>
            </button>
            <button type="submit" class="admin-btn-primary" wire:loading.attr="disabled" wire:target="saveAudio">
                <span wire:loading.remove wire:target="saveAudio">Simpan Pengaturan Suara</span>
                <span wire:loading wire:target="saveAudio">Menyimpan...</span>
            </button>
        </div>
    </form>
</div>
