<?php

use App\Models\Counter;
use App\Models\Service;
use App\Models\User;
use App\Services\DisplayTvSettingsService;
use App\Services\QueueManager;
use App\Support\YouTubePlaylistUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('allows admin to access display tv settings page', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin/display-tv')
        ->assertSuccessful()
        ->assertDontSee('Monitor Lantai');
});

it('saves display tv settings for youtube playlist and running text', function () {
    $admin = User::factory()->admin()->create();
    $playlistUrl = 'https://www.youtube.com/embed/videoseries?list=PLtest123456789';

    Livewire::actingAs($admin)
        ->test('admin.display-tv')
        ->set('video_enabled', true)
        ->set('video_source', 'youtube')
        ->set('video_url', $playlistUrl)
        ->set('video_muted', false)
        ->set('video_volume', 30)
        ->set('video_duck_volume', 5)
        ->set('running_text_enabled', true)
        ->set('running_text', "Baris satu\nBaris dua")
        ->call('save')
        ->assertHasNoErrors();

    $settings = app(DisplayTvSettingsService::class);

    expect($settings->videoEnabled())->toBeTrue()
        ->and($settings->videoSource())->toBe('youtube')
        ->and($settings->videoUrl())->toBe($playlistUrl)
        ->and($settings->videoVolume())->toBe(30)
        ->and($settings->videoDuckVolume())->toBe(5)
        ->and($settings->runningText())->toBe("Baris satu\nBaris dua");

    $board = app(QueueManager::class)->displayBoard()->toArray();

    expect(collect($board['signage_contents'])->pluck('type')->all())
        ->toContain('video', 'running_text');
});

it('uploads mp4 video for display tv settings', function () {
    Storage::fake('public');

    $admin = User::factory()->admin()->create();
    $upload = UploadedFile::fake()->create('promo.mp4', 1024, 'video/mp4');

    Livewire::actingAs($admin)
        ->test('admin.display-tv')
        ->set('video_source', 'upload')
        ->set('video_upload', $upload)
        ->assertHasNoErrors();

    $settings = app(DisplayTvSettingsService::class);

    expect($settings->videoEnabled())->toBeTrue()
        ->and($settings->videoSource())->toBe('upload')
        ->and($settings->videoMediaAssets())->toHaveCount(1);

    $board = app(QueueManager::class)->displayBoard()->toArray();
    $video = collect($board['signage_contents'])->firstWhere('type', 'video');

    expect($video['media_urls'])->toHaveCount(1)
        ->and($video['media_urls'][0])->not->toBeEmpty();

    Storage::disk('public')->assertExists($settings->videoMediaAsset()->path);
});

it('supports multiple uploaded videos in playlist order', function () {
    Storage::fake('public');

    $admin = User::factory()->admin()->create();
    $first = UploadedFile::fake()->create('first.mp4', 512, 'video/mp4');
    $second = UploadedFile::fake()->create('second.mp4', 512, 'video/mp4');

    $component = Livewire::actingAs($admin)
        ->test('admin.display-tv')
        ->set('video_source', 'upload');

    $component->set('video_upload', $first)->assertHasNoErrors();
    $component->set('video_upload', $second)->assertHasNoErrors();

    $settings = app(DisplayTvSettingsService::class);

    expect($settings->videoMediaAssets())->toHaveCount(2);

    $board = app(QueueManager::class)->displayBoard()->toArray();
    $video = collect($board['signage_contents'])->firstWhere('type', 'video');

    expect($video['media_urls'])->toHaveCount(2);
});

it('removes uploaded video from playlist', function () {
    Storage::fake('public');

    $admin = User::factory()->admin()->create();
    $upload = UploadedFile::fake()->create('promo.mp4', 1024, 'video/mp4');

    $component = Livewire::actingAs($admin)
        ->test('admin.display-tv')
        ->set('video_upload', $upload)
        ->assertHasNoErrors();

    $mediaId = app(DisplayTvSettingsService::class)->videoMediaAsset()->id;

    $component->call('removeVideo', $mediaId)
        ->assertHasNoErrors();

    expect(app(DisplayTvSettingsService::class)->videoMediaAssets())->toHaveCount(0);
});

it('rejects invalid youtube url without playlist id', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test('admin.display-tv')
        ->set('video_enabled', true)
        ->set('video_source', 'youtube')
        ->set('video_url', 'https://www.youtube.com/embed/dQw4w9WgXcQ')
        ->call('save')
        ->assertHasErrors(['video_url']);
});

it('requires ducked video volume to stay below the normal volume', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test('admin.display-tv')
        ->set('video_enabled', true)
        ->set('video_source', 'youtube')
        ->set('video_url', 'https://www.youtube.com/embed/videoseries?list=PLtest123456789')
        ->set('video_volume', 20)
        ->set('video_duck_volume', 30)
        ->call('save')
        ->assertHasErrors(['video_duck_volume' => ['lte']]);
});

it('shows one global board for every clinic counter', function () {
    $registration = Service::factory()->create(['code' => 'A', 'name' => 'Pendaftaran']);
    $pharmacy = Service::factory()->create(['code' => 'B', 'name' => 'Farmasi']);

    $registrationCounter = Counter::factory()->create(['name' => 'Loket Pendaftaran']);
    $pharmacyCounter = Counter::factory()->create(['name' => 'Loket Farmasi']);
    $registrationCounter->services()->attach($registration);
    $pharmacyCounter->services()->attach($pharmacy);

    $manager = app(QueueManager::class);
    $calledTicket = $manager->issueFromKiosk($registration)->ticket;
    $manager->issueFromKiosk($pharmacy);
    $manager->callNext($registrationCounter);

    $board = $manager->displayBoard()->toArray();

    expect(collect($board['now_serving'])->pluck('ticket_number')->all())
        ->toContain($calledTicket->ticket_number)
        ->and($board)->not->toHaveKey('upcoming');
});

it('exposes running text lines on display board payload', function () {
    app(DisplayTvSettingsService::class)->save([
        'video_enabled' => false,
        'video_source' => 'upload',
        'video_url' => '',
        'running_text_enabled' => true,
        'running_text' => "Pesan A\nPesan B",
    ]);

    $payload = app(QueueManager::class)->displayBoard()->toArray();
    $running = collect($payload['signage_contents'])->firstWhere('type', 'running_text');

    expect($running['body'])->toBe("Pesan A\nPesan B");
});

it('validates youtube playlist embed urls', function () {
    expect(YouTubePlaylistUrl::isValidEmbed('https://www.youtube.com/embed/videoseries?list=PLtest'))
        ->toBeTrue()
        ->and(YouTubePlaylistUrl::isValidEmbed('https://www.youtube.com/embed/dQw4w9WgXcQ?list=PLtest'))
        ->toBeTrue()
        ->and(YouTubePlaylistUrl::isValidEmbed('https://www.youtube.com/embed/dQw4w9WgXcQ'))
        ->toBeFalse();
});
