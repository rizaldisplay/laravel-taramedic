<?php

use App\Enums\UserRole;
use App\Models\Counter;
use App\Models\QueueTicket;
use App\Models\Service;
use App\Models\User;
use App\Services\QueueManager;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('uses Jakarta as the default application timezone', function () {
    expect(config('app.timezone'))->toBe('Asia/Jakarta');
});

it('uses NAVA consistently as the application brand', function () {
    config()->set('app.name', 'NAVA');

    $composer = json_decode(file_get_contents(base_path('composer.json')), true, flags: JSON_THROW_ON_ERROR);

    $this->get('/login')
        ->assertSuccessful()
        ->assertSee('NAVA')
        ->assertDontSee('QueueFlow');

    expect($composer['name'])->toBe('ruangcoding/nava');
});

it('ships a one-click Windows kiosk launcher with all required processes', function () {
    $launcher = file_get_contents(base_path('start-nava-kiosk.bat'));
    $guide = file_get_contents(base_path('README.md'));

    expect($launcher)
        ->toContain('php artisan serve --host=0.0.0.0')
        ->toContain('php artisan reverb:start --host=0.0.0.0')
        ->toContain('php artisan queue:work')
        ->toContain('php artisan schedule:work')
        ->toContain('--kiosk')
        ->toContain('--autoplay-policy=no-user-gesture-required')
        ->toContain('/display?autostart=1')
        ->toContain('--user-data-dir=')
        ->and($guide)
        ->toContain('Panduan Instalasi dan Implementasi Kiosk')
        ->toContain('Mode operasional permanen')
        ->toContain('Windows Firewall')
        ->toContain('Pengujian penerimaan');
});

it('uses an index friendly comparison for queue dates', function () {
    $sql = strtolower(QueueTicket::query()->forDate('2026-08-14')->toSql());

    expect($sql)->not->toContain('date(')
        ->and($sql)->toContain('queue_date');
});

it('uses a single-floor schema without monitor registry or operator schedules', function () {
    expect(Schema::hasTable('display_screens'))->toBeFalse()
        ->and(Schema::hasTable('operator_schedules'))->toBeFalse()
        ->and(Schema::hasColumn('counters', 'floor'))->toBeFalse()
        ->and(Schema::hasColumn('counters', 'location'))->toBeFalse()
        ->and(Schema::hasColumn('services', 'daily_quota'))->toBeFalse()
        ->and(Schema::hasColumn('services', 'color'))->toBeFalse()
        ->and(Schema::hasColumn('announcement_configs', 'voice_mode'))->toBeTrue()
        ->and(Schema::hasColumn('announcement_configs', 'audio_volume'))->toBeTrue()
        ->and(Schema::hasColumn('announcement_configs', 'number_mode'))->toBeFalse()
        ->and(Schema::hasColumn('announcement_configs', 'queue_label_enabled'))->toBeFalse()
        ->and(Schema::hasColumn('announcement_configs', 'cue_gap_ms'))->toBeTrue()
        ->and(Schema::hasColumn('announcement_configs', 'speak_zero_digits'))->toBeTrue()
        ->and(Schema::hasColumn('announcement_configs', 'offline_audio_asset_id'))->toBeFalse();
});

it('keeps test demo users deterministic while source requires configurable local credentials', function () {
    $this->seed(UserSeeder::class);

    $admin = User::query()->where('email', 'admin@antrian.test')->firstOrFail();
    $source = file_get_contents(database_path('seeders/UserSeeder.php'));

    expect(Hash::check('password', $admin->password))->toBeTrue()
        ->and($source)->toContain('QUEUE_DEMO_PASSWORD')
        ->and($source)->toContain('Str::password(24)');
});

it('prevents demoting the signed in administrator', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test('admin.manage-users')
        ->call('edit', $admin->id)
        ->set('role', UserRole::Operator->value)
        ->call('save')
        ->assertHasErrors(['role']);

    expect($admin->fresh()->role)->toBe(UserRole::Admin);
});

it('protects the final administrator from deletion', function () {
    $admin = User::factory()->admin()->create();
    $operator = User::factory()->create();

    expect($operator->can('delete', $admin))->toBeFalse();
});

it('deactivates a service with queue history instead of deleting it', function () {
    $admin = User::factory()->admin()->create();
    $service = Service::factory()->create();
    app(QueueManager::class)->issueFromKiosk($service);

    Livewire::actingAs($admin)
        ->test('admin.manage-services')
        ->call('delete', $service->id)
        ->assertHasNoErrors();

    expect($service->fresh())->not->toBeNull()
        ->and($service->fresh()->is_active)->toBeFalse();
});

it('deactivates a counter with queue history instead of deleting it', function () {
    $admin = User::factory()->admin()->create();
    $service = Service::factory()->create();
    $counter = Counter::factory()->create();
    $counter->services()->attach($service);

    $manager = app(QueueManager::class);
    $manager->issueFromKiosk($service);
    $manager->callNext($counter);

    Livewire::actingAs($admin)
        ->test('admin.manage-counters')
        ->call('delete', $counter->id)
        ->assertHasNoErrors();

    expect($counter->fresh())->not->toBeNull()
        ->and($counter->fresh()->is_active)->toBeFalse();
});
