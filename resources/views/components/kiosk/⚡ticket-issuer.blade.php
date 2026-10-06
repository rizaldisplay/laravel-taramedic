<?php

use App\Exceptions\Queue\ServiceNotAvailableException;
use App\Models\Service;
use App\Services\QueueManager;
use App\Services\ReceiptSettingsService;
use App\Services\SettingsService;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.kiosk')] class extends Component
{
    public bool $started = false;

    public ?int $selectedServiceId = null;

    public ?array $issuedTicket = null;

    public ?string $errorMessage = null;

    public function start(): void
    {
        $this->started = true;
        $this->errorMessage = null;
    }

    public function selectService(int $serviceId, QueueManager $queue): void
    {
        $this->started = true;
        $this->selectedServiceId = $serviceId;
        $this->errorMessage = null;

        $this->issueTicket($queue);
    }

    public function issueTicket(QueueManager $queue): void
    {
        $this->errorMessage = null;
        $this->issuedTicket = null;

        if ($this->selectedServiceId === null) {
            $this->errorMessage = 'Pilih layanan dahulu.';

            return;
        }

        $rateLimitKey = 'kiosk-issue:'.request()->ip();

        if (RateLimiter::tooManyAttempts($rateLimitKey, 10)) {
            $this->errorMessage = 'Terlalu banyak permintaan. Coba lagi sebentar.';

            return;
        }

        RateLimiter::hit($rateLimitKey, 60);

        try {
            $service = Service::query()->active()->findOrFail($this->selectedServiceId);
            $result = $queue->issueFromKiosk($service);
            $ticketNumber = $result->ticket->ticket_number;

            $this->issuedTicket = [
                'ticket_number' => $ticketNumber,
                'ticket_prefix' => $result->ticket->service->code,
                'ticket_sequence' => preg_replace('/\D+/', '', $ticketNumber) ?: $ticketNumber,
                'service_name' => $result->ticket->service->name,
                'service_code' => $result->ticket->service->code,
                'estimated_wait_minutes' => $result->estimatedWaitMinutes,
                'waiting_count' => $result->waitingCount,
                'issued_at' => $result->ticket->issued_at?->format('H:i') ?? now()->format('H:i'),
                'queue_date' => $result->ticket->queue_date?->locale('id')->translatedFormat('d M Y') ?? now()->locale('id')->translatedFormat('d M Y'),
            ];

            $this->dispatch('queue-updated');
            $payload = json_encode($this->issuedTicket, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
            $this->js('window.kioskReceiptPrinter && window.kioskReceiptPrinter.printTicket('.$payload.')');
        } catch (ServiceNotAvailableException $exception) {
            $this->errorMessage = $exception->getMessage();
        }
    }

    public function resetKiosk(): void
    {
        $this->started = false;
        $this->selectedServiceId = null;
        $this->issuedTicket = null;
        $this->errorMessage = null;
    }

    public function with(): array
    {
        $settings = app(SettingsService::class);
        $receiptSettings = app(ReceiptSettingsService::class);

        return [
            'services' => Service::query()->active()->ordered()->get(),
            'welcomeMessage' => $settings->getString('kiosk_welcome_message')
                ?: 'Silakan tekan tombol mulai untuk mengambil nomor antrean.',
            'institutionName' => $settings->institutionName(),
            'institutionTagline' => $settings->institutionTagline(),
            'institutionAddress' => $settings->getString('institution_address', '') ?? '',
            'institutionPhone' => $settings->getString('institution_phone', '') ?? '',
            'institutionLogoUrl' => $settings->institutionLogoUrl(),
            'receiptSettings' => $receiptSettings->printerOptions(),
            'resetSeconds' => $settings->getInt('kiosk_success_reset_seconds', (int) config('queue_system.kiosk_success_reset_seconds', 15)),
            'idleSeconds' => $settings->getInt('kiosk_idle_reset_seconds', (int) config('queue_system.kiosk_idle_reset_seconds', 60)),
        ];
    }
};
?>

    <div
    class="kiosk-reference-shell"
    x-data="kioskShell(@js([
        'institutionName' => $institutionName,
        'institutionAddress' => $institutionAddress,
        'institutionPhone' => $institutionPhone,
        'institutionLogoUrl' => $institutionLogoUrl,
        'receiptSettings' => $receiptSettings,
        'idleSeconds' => $idleSeconds,
    ]))"
    x-init="boot()"
    @click="resetIdle()"
    @keydown="resetIdle()"
>
    @if (! $started)
        <x-kiosk.institution-header
            :institution-name="$institutionName"
            :institution-tagline="$institutionTagline"
            :institution-logo-url="$institutionLogoUrl"
        />
    @elseif ($issuedTicket)
        <x-kiosk.process-header title="Nomor Antrean Anda" />
    @else
        <x-kiosk.process-header title="Pilih Layanan" />
    @endif

    <main class="kiosk-reference-main">
        @if ($issuedTicket)
            <section class="kiosk-screen kiosk-success-screen" x-data="kioskSuccess(@js($resetSeconds))" x-init="boot()">
                <div class="kiosk-result-card" aria-live="polite">
                    <div class="kiosk-success-check" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none"><path d="m5 12.5 4.2 4.2L19.5 6.5" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </div>
                    <div class="kiosk-confetti kiosk-confetti-left" aria-hidden="true"></div>
                    <div class="kiosk-confetti kiosk-confetti-right" aria-hidden="true"></div>
                    <p class="kiosk-result-service">{{ $issuedTicket['service_name'] }}</p>
                    <p class="kiosk-result-number" aria-label="Nomor antrean {{ $issuedTicket['ticket_number'] }}">
                        <span>{{ $issuedTicket['ticket_prefix'] }}</span><span aria-hidden="true">-</span><span class="tabular-nums">{{ $issuedTicket['ticket_sequence'] }}</span>
                    </p>
                    <p class="kiosk-result-note">Silakan menunggu hingga nomor Anda dipanggil</p>
                </div>

                <aside class="kiosk-success-side" aria-label="Petunjuk setelah mengambil nomor">
                    <div class="kiosk-success-summary">
                        <span aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="m5 12.5 4.2 4.2L19.5 6.5" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                        <p><strong>Tiket berhasil dicetak</strong><small>Simpan tiket Anda sampai nomor dipanggil.</small></p>
                    </div>

                    <div class="kiosk-success-instructions">
                        <article class="kiosk-instruction-card">
                            <span class="kiosk-instruction-icon is-printer" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none"><path d="M7 8V3h10v5M7 17H5a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M7 14h10v7H7v-7Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M17 11h.01" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/></svg>
                            </span>
                            <span><strong>Silakan ambil tiket</strong><small>yang telah dicetak</small></span>
                        </article>
                        <article class="kiosk-instruction-card">
                            <span class="kiosk-instruction-icon is-bell" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none"><path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9ZM10 21h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><circle cx="18.5" cy="16.5" r="3.5" fill="#24a148"/><path d="M18.5 15v.1m0 1.4V19" stroke="white" stroke-width="1.3" stroke-linecap="round"/></svg>
                            </span>
                            <span><strong>Perhatikan layar panggilan</strong><small>untuk menunggu giliran Anda</small></span>
                        </article>
                    </div>

                    <div class="kiosk-success-actions">
                        <button type="button" class="kiosk-button kiosk-button-outline" @click="printTicket(@js($issuedTicket))">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20 11a8 8 0 1 1-2.34-5.66M20 4v7h-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            Cetak Lagi
                        </button>
                        <button type="button" wire:click="resetKiosk" class="kiosk-button kiosk-button-primary">
                            <span class="kiosk-button-circle" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M5 12h14m-5-5 5 5-5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                            Selesai
                        </button>
                    </div>

                    <div class="kiosk-success-footer">
                        <p><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/><path d="M12 10v6m0-9h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>Kembali otomatis dalam <strong class="tabular-nums" x-text="seconds"></strong> detik</p>
                        <p><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/><path d="M12 7v5l3.5 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg><span x-text="dateTime"></span></p>
                    </div>
                </aside>
            </section>
        @elseif (! $started)
            <section class="kiosk-screen kiosk-welcome-screen">
                <div class="kiosk-welcome-copy">
                    <h1>Selamat Datang</h1>
                    <span class="kiosk-heading-accent" aria-hidden="true"></span>
                    <p>{{ $welcomeMessage }}</p>
                </div>

                <div class="kiosk-hero-visual" aria-hidden="true">
                    <span class="kiosk-hero-cross">+</span>
                    <span class="kiosk-hero-cloud kiosk-hero-cloud-one"></span>
                    <span class="kiosk-hero-cloud kiosk-hero-cloud-two"></span>
                    <img src="{{ asset('images/kiosk/clinic-hero.png') }}" alt="">
                </div>

                <button type="button" wire:click="start" wire:loading.attr="disabled" wire:target="start" class="kiosk-start-button">
                    <span class="kiosk-start-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M5 12h14m-5-5 5 5-5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                    <span wire:loading.remove wire:target="start">Mulai</span>
                    <span wire:loading wire:target="start">Memuat...</span>
                </button>

                <p class="kiosk-touch-hint">
                    <svg viewBox="0 0 32 32" fill="none" aria-hidden="true"><path d="M15 16V8a2 2 0 1 1 4 0v6-3a2 2 0 1 1 4 0v4-2a2 2 0 1 1 4 0v7c0 5-3 9-9 9h-2c-4 0-6-2-8-5l-3-5a2 2 0 0 1 3-2l3 3v-4a2 2 0 1 1 4 0Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M7 12a8 8 0 0 1 11-8M4 14A11 11 0 0 1 19 1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                    Sentuh layar untuk memulai
                </p>
            </section>
        @else
            <section class="kiosk-screen kiosk-service-screen">

                @if ($errorMessage)
                    <div class="kiosk-alert" role="alert">{{ $errorMessage }}</div>
                @endif

                <div class="kiosk-service-grid">
                    @forelse ($services as $service)
                        <button type="button" wire:click="selectService({{ $service->id }})" wire:loading.attr="disabled" wire:target="selectService" wire:key="kiosk-service-{{ $service->id }}" class="kiosk-service-card">
                            <x-kiosk.service-icon :name="$service->name" :code="$service->code" :index="$loop->index" />
                            <span class="kiosk-service-card-copy"><strong>{{ $service->name }}</strong><small>Layanan {{ str($service->name)->lower() }}</small></span>
                            <span class="kiosk-service-arrow" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="m9 6 6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                        </button>
                    @empty
                        <p class="kiosk-empty-services">Belum ada layanan yang tersedia.</p>
                    @endforelse
                </div>

                <div class="kiosk-service-footer">
                    <button type="button" wire:click="resetKiosk" class="kiosk-back-button"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M19 12H5m5-5-5 5 5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>Kembali</button>
                    <p><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/><path d="M12 10v6m0-9h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>Nomor antrean akan dicetak setelah Anda memilih layanan</p>
                </div>
            </section>
        @endif
    </main>
</div>
