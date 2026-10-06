<?php

use App\Models\QueueTicket;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders the reference kiosk welcome screen', function () {
    Service::factory()->create([
        'code' => 'A',
        'name' => 'Poli Umum',
        'is_active' => true,
    ]);

    $this->get(route('kiosk'))
        ->assertSuccessful()
        ->assertSee('Selamat Datang')
        ->assertSee('Mulai')
        ->assertSee('Sentuh layar untuk memulai')
        ->assertSee('kiosk-reference-shell', false)
        ->assertSee('kiosk-header-brand', false)
        ->assertSee('images/kiosk/clinic-hero.png', false)
        ->assertDontSee('kiosk-process-header', false)
        ->assertDontSee('kiosk-step-progress', false)
        ->assertSee('Status printer', false);
});

it('uses a focused kiosk flow and issues a ticket directly after selecting a service', function () {
    $service = Service::factory()->create([
        'code' => 'B',
        'name' => 'Poli Umum',
        'is_active' => true,
        'estimated_minutes' => 12,
    ]);

    Livewire::test('kiosk.ticket-issuer')
        ->assertSee('Selamat Datang')
        ->call('start')
        ->assertSet('started', true)
        ->assertSee('Pilih Layanan')
        ->assertSee('Poli Umum')
        ->assertSee('kiosk-process-header', false)
        ->assertDontSee('kiosk-header-brand', false)
        ->assertDontSee('kiosk-step-progress', false)
        ->assertDontSee('Langkah')
        ->assertDontSee('kiosk-service-illustration', false)
        ->assertDontSee('Konfirmasi layanan')
        ->call('selectService', $service->id)
        ->assertSet('selectedServiceId', $service->id)
        ->assertSet('errorMessage', null)
        ->assertSee('Nomor Antrean Anda')
        ->assertSee('Poli Umum')
        ->assertSee('kiosk-success-side', false)
        ->assertSee('Tiket berhasil dicetak')
        ->assertSee('Silakan ambil tiket')
        ->assertSee('Perhatikan layar panggilan')
        ->assertSee('Cetak Lagi')
        ->assertSee('Selesai')
        ->assertDontSee('kiosk-header-brand', false)
        ->assertDontSee('kiosk-step-progress', false)
        ->assertDontSee('Langkah')
        ->assertDontSee('kiosk-success-illustration', false)
        ->assertDontSee('images/kiosk/clinic-hero.png', false)
        ->assertDontSee('Pilih Layanan');

    expect(QueueTicket::query()->count())->toBe(1);
});

it('includes tactile service controls and the two-column success layout', function () {
    $styles = file_get_contents(resource_path('css/queue-ui.css'));
    $receiptPrinter = file_get_contents(resource_path('js/kiosk/receipt-printer.js'));
    $escpos = file_get_contents(resource_path('js/kiosk/escpos.js'));

    expect($styles)
        ->toContain('.kiosk-service-card:active')
        ->toContain('translateY(.48rem) scale(.995)')
        ->toContain('.kiosk-success-side')
        ->toContain('grid-template-columns: minmax(0, 1.15fr) minmax(24rem, .85fr)')
        ->not->toContain('.kiosk-step-progress')
        ->and($receiptPrinter)
        ->toContain('receiptSettings: initialConfig.receiptSettings || {}')
        ->toContain('await encodeEscPosReceipt(ticket')
        ->and($escpos)
        ->toContain('export async function encodeEscPosReceipt')
        ->toContain('show_service_name')
        ->toContain('service_name_label')
        ->toContain('wrapText(institutionAddress, chars)')
        ->toContain('waiting_count_suffix')
        ->not->toContain('waiting_count_format')
        ->not->toContain('imageToEscPosRaster')
        ->not->toContain('show_logo');
});

it('only displays active services and protects service actions while loading', function () {
    Service::factory()->create(['name' => 'Poli Gigi', 'is_active' => true]);
    Service::factory()->inactive()->create(['name' => 'Layanan Nonaktif']);

    Livewire::test('kiosk.ticket-issuer')
        ->call('start')
        ->assertSee('Poli Gigi')
        ->assertDontSee('Layanan Nonaktif')
        ->assertSee('wire:loading.attr="disabled"', false)
        ->assertSee('wire:target="selectService"', false);
});

it('resets the success screen without issuing another ticket', function () {
    $service = Service::factory()->create(['code' => 'C', 'name' => 'Poli KIA']);

    Livewire::test('kiosk.ticket-issuer')
        ->call('start')
        ->call('selectService', $service->id)
        ->assertSee('Cetak Lagi')
        ->call('resetKiosk')
        ->assertSet('started', false)
        ->assertSet('selectedServiceId', null)
        ->assertSet('issuedTicket', null)
        ->assertSee('Selamat Datang');

    expect(QueueTicket::query()->count())->toBe(1);
});
