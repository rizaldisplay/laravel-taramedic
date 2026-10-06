<?php

use App\DTOs\Queue\QueueStatisticsData;
use App\Enums\QueueTicketStatus;
use App\Enums\UserRole;
use App\Models\Counter;
use App\Models\MediaAsset;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use App\Services\QueueManager;
use App\Services\QueueStatisticsService;
use App\Services\ReceiptSettingsService;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('allows admin to open reports and users pages', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin/reports')
        ->assertSuccessful();

    $this->actingAs($admin)
        ->get('/admin/users')
        ->assertSuccessful();
});

it('forbids operators from reports and users pages', function () {
    $operator = User::factory()->create();

    $this->actingAs($operator)
        ->get('/admin/reports')
        ->assertForbidden();

    $this->actingAs($operator)
        ->get('/admin/users')
        ->assertForbidden();
});

it('lists report tickets filtered by service', function () {
    $admin = User::factory()->admin()->create();
    $service = Service::factory()->create(['code' => 'R']);
    $other = Service::factory()->create(['code' => 'O']);
    $counter = Counter::factory()->create();
    $counter->services()->attach([$service->id, $other->id]);

    $manager = app(QueueManager::class);
    $ticket = $manager->issueFromKiosk($service)->ticket;
    $manager->issueFromKiosk($other);

    Livewire::actingAs($admin)
        ->test('admin.queue-reports')
        ->set('serviceId', $service->id)
        ->assertSee($ticket->ticket_number)
        ->assertDontSee('O001');
});

it('keeps report summary metrics aligned with the selected filters', function () {
    $admin = User::factory()->admin()->create();
    $service = Service::factory()->create(['code' => 'RS']);
    $other = Service::factory()->create(['code' => 'RO']);
    $counter = Counter::factory()->create();
    $counter->services()->attach([$service->id, $other->id]);

    $manager = app(QueueManager::class);

    $first = $manager->issueFromKiosk($service);
    $firstCall = $manager->callNext($counter);
    $manager->complete($manager->startServing($firstCall->ticket));

    $second = $manager->issueFromKiosk($other);

    $summary = $manager->reportSummary([
        'date_from' => today()->toDateString(),
        'date_to' => today()->toDateString(),
        'service_id' => $service->id,
        'status' => QueueTicketStatus::Completed,
    ]);

    expect($summary->total)->toBe(1)
        ->and($summary->completed)->toBe(1)
        ->and($summary->waiting)->toBe(0)
        ->and($second->ticket->fresh()->status)->toBe(QueueTicketStatus::Waiting);

    Livewire::actingAs($admin)
        ->test('admin.queue-reports')
        ->set('serviceId', $service->id)
        ->set('status', QueueTicketStatus::Completed->value)
        ->assertSee($first->ticket->ticket_number)
        ->assertDontSee($second->ticket->ticket_number);
});

it('ignores invalid report enum filters from manipulated urls', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test('admin.queue-reports')
        ->set('status', 'invalid-status')
        ->set('source', 'invalid-source')
        ->assertSee('Laporan Antrian')
        ->assertHasNoErrors();
});

it('does not render unused operating schedule settings', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test('admin.settings')
        ->assertSee('Informasi Instansi')
        ->assertDontSee('Jam Operasional')
        ->assertDontSee('Hari Operasional')
        ->assertDontSee('Senin')
        ->assertDontSee('Minggu');

    $settingsSource = file_get_contents(resource_path('views/components/admin/⚡settings.blade.php'));
    $institutionTabSource = file_get_contents(resource_path('views/partials/admin/settings/institution-tab.blade.php'));
    $seederSource = file_get_contents(database_path('seeders/QueueSystemSeeder.php'));

    expect($settingsSource)
        ->not->toContain('operating_hours')
        ->not->toContain('operating_days')
        ->and($institutionTabSource)
        ->not->toContain('Jam Operasional')
        ->not->toContain('Hari Operasional')
        ->and($seederSource)
        ->not->toContain('operating_hours');
});

it('saves dynamic receipt print visibility and labels', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test('admin.settings')
        ->set('tab', 'receipt')
        ->set('receipt.show_institution_name', false)
        ->set('receipt.show_service_code', false)
        ->set('receipt.show_waiting_count', false)
        ->set('receipt.service_name_label', 'Nama Poli')
        ->set('receipt.estimated_wait_label', 'Perkiraan Tunggu')
        ->set('receipt.ticket_number_label', 'Nomor Pasien')
        ->set('receipt.waiting_count_label', 'Sisa Antrean')
        ->call('saveReceiptSettings')
        ->assertHasNoErrors()
        ->assertSee('Pengaturan cetak struk berhasil disimpan');

    $receipt = app(ReceiptSettingsService::class)->printerOptions();

    expect($receipt['show_institution_name'])->toBeFalse()
        ->and($receipt['show_service_code'])->toBeFalse()
        ->and($receipt['show_waiting_count'])->toBeFalse()
        ->and($receipt['service_name_label'])->toBe('Nama Poli')
        ->and($receipt['estimated_wait_label'])->toBe('Perkiraan Tunggu')
        ->and($receipt['ticket_number_label'])->toBe('Nomor Pasien')
        ->and($receipt['waiting_count_label'])->toBe('Sisa Antrean')
        ->and($receipt['waiting_count_suffix'])->toBe('orang')
        ->and($receipt)->not->toHaveKey('waiting_count_format')
        ->and($receipt)->not->toHaveKey('show_logo');

    Service::factory()->create(['name' => 'Poli Umum', 'is_active' => true]);

    Livewire::test('kiosk.ticket-issuer')
        ->assertSee('receiptSettings', false)
        ->assertSee('Nama Poli', false)
        ->assertSee('Perkiraan Tunggu', false)
        ->assertSee('Nomor Pasien', false)
        ->assertSee('Sisa Antrean', false)
        ->assertDontSee('waiting_count_format', false);
});

it('uploads institution logo and shares it across admin, auth, display, and kiosk surfaces', function () {
    Storage::fake('public');

    $admin = User::factory()->admin()->create();
    $upload = UploadedFile::fake()->image('klinik-logo.png', 256, 256);

    Livewire::actingAs($admin)
        ->test('admin.settings')
        ->set('institution_logo_upload', $upload)
        ->assertHasNoErrors()
        ->assertSee('Logo institusi berhasil disimpan');

    $logo = MediaAsset::query()->first();
    $settings = app(SettingsService::class);

    expect($logo)->not->toBeNull()
        ->and($settings->institutionLogoMediaId())->toBe($logo->id)
        ->and($settings->institutionLogoUrl())->toContain('/storage/institution/logos/');

    Storage::disk('public')->assertExists($logo->path);

    auth()->logout();

    $this->get(route('login'))->assertSee('/storage/institution/logos/', false);
    $this->get(route('display'))->assertSee('/storage/institution/logos/', false);
    $this->get(route('kiosk'))->assertSee('/storage/institution/logos/', false);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertSee('/storage/institution/logos/', false);
});

it('renders a light-aware sidebar and professional auto dismiss toast styles', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->withSession(['message' => 'Data berhasil disimpan.'])
        ->get(route('admin.settings'))
        ->assertSuccessful()
        ->assertSeeHtml('class="admin-sidebar')
        ->assertSeeHtml('text-ink-950 dark:text-white')
        ->assertSeeHtml('admin-toast-viewport')
        ->assertSeeHtml('x-data="adminToast()"')
        ->assertSee('Data berhasil disimpan.');

    $styles = file_get_contents(resource_path('css/admin-ui.css'));
    $javascript = file_get_contents(resource_path('js/app.js'));
    $flashPartial = file_get_contents(resource_path('views/partials/admin/flash.blade.php'));
    $operatorPanel = file_get_contents(resource_path('views/components/operator/⚡counter-panel.blade.php'));

    expect($styles)
        ->toContain('.admin-sidebar')
        ->toContain('linear-gradient(180deg, #ffffff 0%, #f4f8ff 46%, #eef5ff 100%)')
        ->toContain('.dark .admin-sidebar')
        ->toContain('.admin-toast')
        ->toContain('admin-toast-enter')
        ->toContain('admin-toast-progress 5200ms linear forwards')
        ->and($javascript)
        ->toContain("Alpine.data('adminToast'")
        ->toContain('setTimeout(() => this.close(), duration)')
        ->and($flashPartial)
        ->toContain('admin-toast-close')
        ->toContain('x-cloak')
        ->and($operatorPanel)
        ->toContain('admin-toast-success')
        ->not->toContain('admin-flash-success');
});

it('renders service and counter management as tables with modal forms', function () {
    $admin = User::factory()->admin()->create();
    $service = Service::factory()->create(['name' => 'Poli Umum']);
    $counter = Counter::factory()->create(['name' => 'Ruang Periksa']);
    $counter->services()->attach($service);

    Livewire::actingAs($admin)
        ->test('admin.manage-services')
        ->assertSeeHtml('admin-table-wrap')
        ->assertSee('Poli Umum')
        ->call('create')
        ->assertSeeHtml('admin-modal-root')
        ->assertSee('Kode antrean')
        ->call('save')
        ->assertHasErrors(['code', 'name']);

    Livewire::actingAs($admin)
        ->test('admin.manage-counters')
        ->assertSeeHtml('admin-table-wrap')
        ->assertSee('Ruang Periksa')
        ->call('create')
        ->assertSeeHtml('admin-modal-root')
        ->assertSee('Layanan yang ditangani')
        ->call('save')
        ->assertHasErrors(['code', 'name', 'serviceIds']);
});

it('creates and updates users from admin panel', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test('admin.manage-users')
        ->call('create')
        ->assertSeeHtml('admin-modal-root')
        ->assertSee('Peran pengguna')
        ->set('name', 'Petugas Baru')
        ->set('email', 'petugas@example.test')
        ->set('role', UserRole::Operator->value)
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('save')
        ->assertHasNoErrors();

    $user = User::query()->where('email', 'petugas@example.test')->first();

    expect($user)->not->toBeNull()
        ->and($user->role)->toBe(UserRole::Operator);

    Livewire::actingAs($admin)
        ->test('admin.manage-users')
        ->call('edit', $user->id)
        ->set('name', 'Petugas Update')
        ->set('password', '')
        ->set('password_confirmation', '')
        ->call('save')
        ->assertHasNoErrors();

    expect($user->fresh()->name)->toBe('Petugas Update');
});

it('prevents admin from deleting themselves', function () {
    $admin = User::factory()->admin()->create();

    expect($admin->can('delete', $admin))->toBeFalse();
});

it('caches settings and clears cache on update', function () {
    Cache::flush();

    $settings = app(SettingsService::class);
    $settings->setString('institution_name', 'Klinik A');

    expect($settings->institutionName())->toBe('Klinik A');

    Setting::query()->where('key', 'institution_name')->update([
        'value' => ['value' => 'Klinik B'],
    ]);

    // Still cached
    expect(app(SettingsService::class)->institutionName())->toBe('Klinik A');

    app(SettingsService::class)->forgetCache();

    expect(app(SettingsService::class)->institutionName())->toBe('Klinik B');
});

it('builds statistics with sql aggregates and cache', function () {
    $service = Service::factory()->create(['code' => 'S']);
    $counter = Counter::factory()->create();
    $counter->services()->attach($service);

    $manager = app(QueueManager::class);
    $manager->issueFromKiosk($service);
    $called = $manager->callNext($counter);
    $manager->complete($manager->startServing($called->ticket));

    $stats = app(QueueStatisticsService::class)->forDate();

    expect($stats->total)->toBe(1)
        ->and($stats->completed)->toBe(1)
        ->and($stats->byService)->not->toBeEmpty();
});

it('recovers when legacy statistics cache stores a non-array payload', function () {
    $date = today()->toDateString();
    $key = "queue.stats.{$date}";

    Cache::put($key, 'legacy-invalid-payload', 60);

    $stats = app(QueueStatisticsService::class)->forDate($date);

    expect($stats)->toBeInstanceOf(QueueStatisticsData::class)
        ->and(Cache::get($key))->toBeArray();
});
