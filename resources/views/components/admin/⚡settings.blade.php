<?php

use App\Enums\AnnouncementVoiceMode;
use App\Enums\AnnouncementVoiceStyle;
use App\Services\AnnouncementSettingsService;
use App\Services\ReceiptSettingsService;
use App\Services\SettingsService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.app')] class extends Component
{
    use WithFileUploads;

    #[Url]
    public string $tab = 'institution';

    public string $institution_name = '';

    public string $institution_tagline = '';

    public string $institution_address = '';

    public string $institution_phone = '';

    public string $kiosk_welcome_message = '';

    public $institution_logo_upload = null;

    public ?array $institution_logo = null;

    /** @var array<string, mixed> */
    public array $audio = [];

    /** @var array<string, mixed> */
    public array $receipt = [];

    public int $display_recent_limit = 4;

    public int $kiosk_success_reset_seconds = 15;

    public int $kiosk_idle_reset_seconds = 60;

    public int $called_no_show_after_minutes = 5;

    public function mount(
        SettingsService $settings,
        ReceiptSettingsService $receiptSettings,
        AnnouncementSettingsService $announcementSettings,
    ): void
    {
        Gate::authorize('access-admin');
        $this->institution_name = $settings->getString('institution_name', config('queue_system.institution_name', ''));
        $this->institution_tagline = $settings->getString('institution_tagline', config('queue_system.institution_tagline', ''));
        $this->institution_address = $settings->getString('institution_address', '');
        $this->institution_phone = $settings->getString('institution_phone', '');
        $this->kiosk_welcome_message = $settings->getString('kiosk_welcome_message', '');
        $this->hydrateInstitutionLogo($settings);
        $this->receipt = $receiptSettings->toFormState();

        $this->display_recent_limit = $settings->getInt('display_recent_limit', (int) config('queue_system.display_recent_limit', 4));
        $this->kiosk_success_reset_seconds = $settings->getInt('kiosk_success_reset_seconds', (int) config('queue_system.kiosk_success_reset_seconds', 15));
        $this->kiosk_idle_reset_seconds = $settings->getInt('kiosk_idle_reset_seconds', (int) config('queue_system.kiosk_idle_reset_seconds', 60));
        $this->called_no_show_after_minutes = $settings->getInt(
            'called_no_show_after_minutes',
            (int) config('queue_system.called_no_show_after_minutes', 5),
        );

        $this->audio = $announcementSettings->formState();
    }

    public function updatedInstitutionLogoUpload(SettingsService $settings): void
    {
        $this->saveInstitutionLogo($settings);
    }

    public function saveInstitutionLogo(SettingsService $settings): void
    {
        Gate::authorize('access-admin');

        if ($this->institution_logo_upload === null) {
            return;
        }

        $this->validate([
            'institution_logo_upload' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ]);

        try {
            $settings->saveInstitutionLogo($this->institution_logo_upload);
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('institution_logo_upload', 'Gagal menyimpan logo. Pastikan storage dapat ditulis.');

            return;
        }

        $this->institution_logo_upload = null;
        $this->hydrateInstitutionLogo($settings);

        session()->flash('message', 'Logo institusi berhasil disimpan.');
    }

    public function removeInstitutionLogo(SettingsService $settings): void
    {
        Gate::authorize('access-admin');
        $settings->removeInstitutionLogo();
        $this->hydrateInstitutionLogo($settings);

        session()->flash('message', 'Logo institusi dihapus.');
    }

    public function saveInstitution(SettingsService $settings): void
    {
        Gate::authorize('access-admin');
        $data = $this->validate([
            'institution_name' => ['required', 'string', 'max:255'],
            'institution_tagline' => ['nullable', 'string', 'max:255'],
            'institution_address' => ['nullable', 'string', 'max:500'],
            'institution_phone' => ['nullable', 'string', 'max:50'],
            'kiosk_welcome_message' => ['nullable', 'string', 'max:500'],
        ]);

        $settings->setString('institution_name', $data['institution_name']);
        $settings->setString('institution_tagline', $data['institution_tagline'] ?? '', 'general');
        $settings->setString('institution_address', $data['institution_address'] ?? '');
        $settings->setString('institution_phone', $data['institution_phone'] ?? '');
        $settings->setString('kiosk_welcome_message', $data['kiosk_welcome_message'] ?? '', 'kiosk');

        session()->flash('message', 'Informasi instansi berhasil disimpan.');
    }

    public function saveQueueSettings(SettingsService $settings): void
    {
        Gate::authorize('access-admin');
        $data = $this->validate([
            'display_recent_limit' => ['required', 'integer', 'min:1', 'max:8'],
            'kiosk_success_reset_seconds' => ['required', 'integer', 'min:5', 'max:120'],
            'kiosk_idle_reset_seconds' => ['required', 'integer', 'min:15', 'max:300'],
            'called_no_show_after_minutes' => ['required', 'integer', 'min:0', 'max:120'],
        ]);

        $settings->set('display_recent_limit', ['value' => $data['display_recent_limit']], 'display');
        $settings->set('kiosk_success_reset_seconds', ['value' => $data['kiosk_success_reset_seconds']], 'kiosk');
        $settings->set('kiosk_idle_reset_seconds', ['value' => $data['kiosk_idle_reset_seconds']], 'kiosk');
        $settings->set('called_no_show_after_minutes', ['value' => $data['called_no_show_after_minutes']], 'queue');

        session()->flash('message', 'Pengaturan antrian berhasil disimpan.');
    }

    public function saveAudio(AnnouncementSettingsService $announcementSettings): void
    {
        Gate::authorize('access-admin');
        $data = $this->validate([
            'audio.voice_mode' => ['required', Rule::enum(AnnouncementVoiceMode::class)],
            'audio.announcement_enabled' => ['required', 'boolean'],
            'audio.audio_volume' => ['required', 'integer', 'min:0', 'max:100'],
            'audio.repeat_count' => ['required', 'integer', 'min:1', 'max:3'],
            'audio.opening_enabled' => ['required', 'boolean'],
            'audio.closing_enabled' => ['required', 'boolean'],
            'audio.cue_gap_ms' => ['required', 'integer', 'min:0', 'max:500'],
            'audio.repeat_pause_ms' => ['required', 'integer', 'min:0', 'max:5000'],
            'audio.tts_template' => ['required', 'string', 'max:500'],
            'audio.speak_zero_digits' => ['required', 'boolean'],
            'audio.tts_speed' => ['required', 'numeric', 'min:0.65', 'max:1'],
            'audio.tts_style' => ['required', Rule::enum(AnnouncementVoiceStyle::class)],
            'audio.online_pitch' => ['required', 'numeric', 'min:0.7', 'max:1.3'],
        ]);

        $announcementSettings->save($data['audio']);
        $this->audio = $announcementSettings->formState();

        session()->flash('message', 'Pengaturan suara berhasil disimpan.');
    }

    public function saveReceiptSettings(ReceiptSettingsService $receiptSettings): void
    {
        Gate::authorize('access-admin');

        $data = $this->validate([
            'receipt.show_institution_name' => ['boolean'],
            'receipt.show_institution_address' => ['boolean'],
            'receipt.show_institution_phone' => ['boolean'],
            'receipt.show_receipt_title' => ['boolean'],
            'receipt.show_ticket_label' => ['boolean'],
            'receipt.show_service_name' => ['boolean'],
            'receipt.show_service_code' => ['boolean'],
            'receipt.show_estimated_wait' => ['boolean'],
            'receipt.show_waiting_count' => ['boolean'],
            'receipt.show_queue_date' => ['boolean'],
            'receipt.show_issued_time' => ['boolean'],
            'receipt.show_footer' => ['boolean'],
            'receipt.receipt_title_label' => ['required', 'string', 'max:80'],
            'receipt.ticket_number_label' => ['required', 'string', 'max:80'],
            'receipt.service_name_label' => ['required', 'string', 'max:80'],
            'receipt.service_code_label' => ['required', 'string', 'max:80'],
            'receipt.estimated_wait_label' => ['required', 'string', 'max:80'],
            'receipt.waiting_count_label' => ['required', 'string', 'max:80'],
            'receipt.queue_date_label' => ['required', 'string', 'max:80'],
            'receipt.issued_time_label' => ['required', 'string', 'max:80'],
            'receipt.estimated_wait_suffix' => ['nullable', 'string', 'max:40'],
            'receipt.waiting_count_suffix' => ['nullable', 'string', 'max:40'],
            'receipt.footer_line_1' => ['nullable', 'string', 'max:120'],
            'receipt.footer_line_2' => ['nullable', 'string', 'max:120'],
            'receipt.thank_you_label' => ['nullable', 'string', 'max:80'],
        ]);

        $receiptSettings->save($data['receipt']);
        $this->receipt = $receiptSettings->toFormState();

        session()->flash('message', 'Pengaturan cetak struk berhasil disimpan.');
    }

    public function with(): array
    {
        return [
            'settingTabs' => [
                'institution' => ['label' => 'Institusi', 'icon' => 'institution'],
                'audio' => ['label' => 'Suara Panggilan', 'icon' => 'audio'],
                'receipt' => ['label' => 'Cetak Struk', 'icon' => 'queue'],
                'queue' => ['label' => 'Sistem Antrian', 'icon' => 'queue'],
            ],
        ];
    }

    public function hydrateInstitutionLogo(SettingsService $settings): void
    {
        $asset = $settings->institutionLogoAsset();

        $this->institution_logo = $asset === null ? null : [
            'id' => $asset->id,
            'name' => $asset->name,
            'url' => $asset->url(),
            'size' => $asset->size,
        ];
    }
};
?>

<div>
    <div class="admin-page-header">
        @include('layouts.partials.page-heading', [
            'title' => 'Pengaturan Sistem',
            'subtitle' => 'Kelola identitas klinik, suara panggilan, dan perilaku antrian',
        ])
    </div>

    @include('partials.admin.flash')

    <x-admin.guide
        :title="match ($tab) {
            'audio' => 'Panduan suara panggilan',
            'receipt' => 'Panduan cetak struk',
            'queue' => 'Panduan perilaku antrean',
            default => 'Panduan identitas klinik',
        }"
        :description="match ($tab) {
            'audio' => 'Pilih TTS Indonesia lokal tanpa internet atau TTS online dari browser perangkat.',
            'receipt' => 'Atur informasi yang benar-benar perlu tercetak agar struk tetap ringkas.',
            'queue' => 'Sesuaikan perilaku antrian dengan kebutuhan pelayanan, lalu uji pada kiosk.',
            default => 'Informasi ini digunakan bersama pada kiosk, display, dan halaman masuk.',
        }"
        :items="match ($tab) {
            'audio' => ['Pilih mode suara sesuai kemampuan perangkat display.', 'Uji suara TTS sampai statusnya siap sebelum menyimpan.', 'Aktifkan suara TV pada perangkat display sebelum pelayanan.'],
            'receipt' => ['Matikan baris yang tidak dibutuhkan supaya cetakan lebih cepat.', 'Gunakan label singkat agar muat di kertas 58mm.', 'Logo akan dicetak jika printer mendukung gambar raster.'],
            'queue' => ['Gunakan nilai awal bila belum ada kebutuhan khusus.', 'Waktu reset kiosk harus cukup bagi pasien membaca tiket.', 'Batas tidak hadir membantu operator menjaga antrean bergerak.'],
            default => ['Gunakan nama klinik resmi.', 'Pastikan alamat dan nomor telepon mudah dikenali pasien.', 'Buat pesan kiosk dalam satu atau dua kalimat.'],
        }"
    />

    <div class="mb-6">
        @include('partials.admin.tab-nav', ['tabs' => $settingTabs, 'active' => $tab])
    </div>

    <div class="admin-card">
        <div class="admin-card-body">
            @if ($tab === 'institution')
                @include('partials.admin.settings.institution-tab')
            @elseif ($tab === 'audio')
                @include('partials.admin.settings.audio-tab')
            @elseif ($tab === 'receipt')
                @include('partials.admin.settings.receipt-tab')
            @elseif ($tab === 'queue')
                @include('partials.admin.settings.queue-tab')
            @endif
        </div>
    </div>
</div>
