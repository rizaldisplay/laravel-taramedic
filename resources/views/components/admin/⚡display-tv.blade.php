<?php

use App\Services\DisplayTvSettingsService;
use App\Support\UploadLimits;
use App\Support\YouTubePlaylistUrl;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.app')] class extends Component
{
    use WithFileUploads;

    public string $activeTab = 'video';

    public bool $video_enabled = false;

    public string $video_source = 'upload';

    public string $video_url = '';

    public bool $video_muted = false;

    public int $video_volume = 25;

    public int $video_duck_volume = 4;

    public bool $running_text_enabled = true;

    public string $running_text = '';

    public $video_upload = null;

    /** @var array<int, array{id: int, name: string, url: ?string, size: int}> */
    public array $video_media_items = [];

    public function mount(DisplayTvSettingsService $displayTv): void
    {
        Gate::authorize('access-admin');
        $this->hydrateVideoState($displayTv);
    }

    public function updatedVideoUpload(): void
    {
        $this->persistVideoUpload();
    }

    public function persistVideoUpload(): void
    {
        Gate::authorize('access-admin');
        if ($this->video_upload === null) {
            return;
        }

        $this->video_enabled = true;
        $this->video_source = 'upload';

        $this->validate([
            'video_upload' => $this->videoUploadRules(required: true),
        ]);

        try {
            app(DisplayTvSettingsService::class)->saveVideoUpload($this->video_upload);
        } catch (\Throwable $exception) {
            $this->video_upload = null;
            report($exception);
            $this->addError('video_upload', 'Gagal menyimpan video. Pastikan storage dapat ditulis (php artisan storage:link).');
            $this->dispatch('display-tv-video-failed', message: 'Gagal menyimpan video ke storage.');

            return;
        }

        $this->video_upload = null;
        $this->hydrateVideoState(app(DisplayTvSettingsService::class));

        session()->flash('message', 'Video berhasil ditambahkan ke daftar putar.');
        $this->dispatch('display-tv-video-saved');
    }

    public function removeVideo(int $mediaId): void
    {
        Gate::authorize('access-admin');
        app(DisplayTvSettingsService::class)->removeVideoMedia($mediaId);
        $this->hydrateVideoState(app(DisplayTvSettingsService::class));
        session()->flash('message', 'Video dihapus dari daftar putar.');
    }

    public function save(DisplayTvSettingsService $displayTv): void
    {
        Gate::authorize('access-admin');
        $rules = [
            'video_enabled' => ['boolean'],
            'video_source' => ['required', Rule::in(['upload', 'youtube', 'url'])],
            'video_url' => ['nullable', 'url', 'max:500'],
            'running_text_enabled' => ['boolean'],
            'running_text' => ['nullable', 'string', 'max:2000'],
            'video_muted' => ['boolean'],
            'video_volume' => ['required', 'integer', 'between:0,100'],
            'video_duck_volume' => ['required', 'integer', 'between:0,100', 'lte:video_volume'],
        ];

        if ($this->video_enabled && in_array($this->video_source, ['youtube', 'url'], true)) {
            $rules['video_url'] = ['required', 'url', 'max:500', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! YouTubePlaylistUrl::isValidEmbed(is_string($value) ? $value : null)) {
                    $fail('Masukkan URL embed playlist YouTube yang valid (harus mengandung parameter list=).');
                }
            }];
        }

        if ($this->video_enabled && $this->video_source === 'upload' && $this->video_media_items === []) {
            session()->flash('error', 'Unggah minimal satu video sebelum menyimpan.');

            return;
        }

        $data = $this->validate($rules);

        $displayTv->save($data);

        $this->hydrateVideoState($displayTv);

        session()->flash('message', 'Pengaturan Display TV berhasil disimpan.');
    }

    /**
     * @return array<int, mixed>
     */
    private function videoUploadRules(bool $required = false): array
    {
        $rules = ['file', 'mimes:'.implode(',', UploadLimits::displayTvVideoMimeRules()), 'max:'.UploadLimits::displayTvVideoMaxKilobytes()];

        if ($required) {
            array_unshift($rules, 'required');
        } else {
            array_unshift($rules, 'nullable');
        }

        return $rules;
    }

    private function hydrateVideoState(DisplayTvSettingsService $displayTv): void
    {
        $state = $displayTv->toFormState();

        $this->video_enabled = $state['video_enabled'];
        $this->video_source = $state['video_source'];
        $this->video_url = $state['video_url'];
        $this->video_muted = $state['video_muted'];
        $this->video_volume = $state['video_volume'];
        $this->video_duck_volume = $state['video_duck_volume'];
        $this->running_text_enabled = $state['running_text_enabled'];
        $this->running_text = $state['running_text'];
        $this->video_media_items = $state['video_media_items'];
    }

    public function with(): array
    {
        return [
            'maxVideoUploadMb' => UploadLimits::displayTvVideoMaxMegabytes(),
        ];
    }
};
?>

<div>
    <div class="admin-page-header">
        @include('layouts.partials.page-heading', [
            'title' => 'Pengaturan Display TV',
            'subtitle' => 'Kelola video dan informasi singkat pada monitor ruang tunggu',
        ])
        <a href="{{ route('display') }}" target="_blank" rel="noopener" class="admin-btn-secondary shrink-0">Buka Monitor</a>
    </div>

    @include('partials.admin.flash')

    <x-admin.guide
        title="Susun informasi ruang tunggu"
        description="Utamakan konten yang tenang, singkat, dan mudah dipahami pasien."
        :items="[
            'Gunakan video edukasi dengan rasio 16:9 dan durasi yang wajar.',
            'Batasi running text pada informasi penting atau arahan singkat.',
            'Buka monitor setelah menyimpan untuk memeriksa hasil sebenarnya.',
        ]"
    />

    <div class="admin-tab-nav mb-6">
        <button
            type="button"
            wire:click="$set('activeTab', 'video')"
            @class(['admin-tab-btn', 'admin-tab-btn-active' => $activeTab === 'video', 'admin-tab-btn-inactive' => $activeTab !== 'video'])
        >
            @include('partials.icons.film', ['class' => 'h-4 w-4'])
            Video
        </button>
        <button
            type="button"
            wire:click="$set('activeTab', 'running-text')"
            @class(['admin-tab-btn', 'admin-tab-btn-active' => $activeTab === 'running-text', 'admin-tab-btn-inactive' => $activeTab !== 'running-text'])
        >
            @include('partials.admin.icons.queue', ['class' => 'h-4 w-4'])
            Running Text
        </button>
    </div>

    <form wire:submit="save">
        @if ($activeTab === 'video')
            @include('partials.admin.display-tv-video-settings', [
                'maxVideoUploadMb' => $maxVideoUploadMb,
            ])
        @endif

        @if ($activeTab === 'running-text')
            <div class="admin-card">
                <div class="admin-card-header">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Running Text</h2>
                        <p class="text-sm text-gray-500">Teks berjalan di bagian bawah layar ruang tunggu</p>
                    </div>
                    <label class="display-tv-toggle">
                        <input type="checkbox" wire:model.live="running_text_enabled" class="display-tv-toggle-input">
                        <span class="display-tv-toggle-track"></span>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Aktif</span>
                    </label>
                </div>

                @if ($running_text_enabled)
                    <div class="admin-card-body space-y-3">
                        <div>
                            <label class="admin-form-label">Isi Running Text</label>
                            <textarea
                                wire:model="running_text"
                                rows="5"
                                class="admin-form-input min-h-[8rem] resize-y"
                                placeholder="Satu baris = satu segmen teks. Pisahkan dengan enter."
                            ></textarea>
                            @error('running_text') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
                        </div>
                        <p class="text-xs text-gray-500">Contoh: "Selamat datang di layanan kami" pada baris pertama, "Harap menunggu panggilan" pada baris kedua.</p>
                    </div>
                @endif
            </div>
        @endif

        @if (in_array($activeTab, ['video', 'running-text'], true))
            <div class="mt-6 flex justify-end">
                <button
                    type="submit"
                    class="admin-btn-primary"
                    wire:loading.attr="disabled"
                    wire:target="save,persistVideoUpload"
                >
                    <span wire:loading.remove wire:target="save,persistVideoUpload,video_upload">Simpan Pengaturan</span>
                    <span wire:loading wire:target="video_upload">Menunggu unggahan...</span>
                    <span wire:loading wire:target="persistVideoUpload">Menyimpan video...</span>
                    <span wire:loading wire:target="save">Menyimpan...</span>
                </button>
            </div>
        @endif
    </form>

</div>
