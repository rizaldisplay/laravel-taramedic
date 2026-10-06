<?php

use App\Models\Counter;
use App\Models\Service;
use App\Services\DisplayTvSettingsService;
use App\Services\QueueManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders display page with latest called number in hero', function () {
    $service = Service::factory()->create(['code' => 'A', 'name' => 'Pendaftaran']);
    $counter = Counter::factory()->create(['name' => 'Loket 1']);
    $counter->services()->attach($service);

    $manager = app(QueueManager::class);
    $ticket = $manager->issueFromKiosk($service)->ticket;
    $manager->callNext($counter);

    Livewire::test('display.queue-board')
        ->assertSee($ticket->ticket_number)
        ->assertSee('Loket 1')
        ->assertSee('Sedang Dipanggil')
        ->assertSeeHtml('display-tv-main--video-dominant')
        ->assertSeeHtml('display-tv-health-poster')
        ->assertSee('Cuci Tangan')
        ->assertSee('Riwayat Antrian Terakhir')
        ->assertDontSeeHtml('display-tv-call-overlay');
});

it('keeps active call in the main card and out of the recent strip', function () {
    $service = Service::factory()->create(['code' => 'B', 'name' => 'Konsultasi']);
    $counter = Counter::factory()->create(['name' => 'Loket A']);
    $counter->services()->attach($service);

    $manager = app(QueueManager::class);
    $ticket = $manager->issueFromKiosk($service)->ticket;
    $manager->callNext($counter);

    $html = Livewire::test('display.queue-board')->html();

    expect($html)
        ->toContain($ticket->ticket_number)
        ->toContain('display-tv-now-serving')
        ->toContain('display-tv-recent-flow')
        ->toContain('Riwayat Antrian Terakhir')
        ->and(substr_count($html, 'display-tv-recent-card--idle'))->toBe(4)
        ->and($html)->not->toContain('data-recent-ticket="'.$ticket->ticket_number.'"');
});

it('renders the reference health information and persistent ticker', function () {
    $html = Livewire::test('display.queue-board')
        ->assertSee('Cuci Tangan')
        ->assertSee('Langkah Kecil')
        ->assertSee('Cegah')
        ->assertSee('Datang lebih awal')
        ->assertSee('Info Kesehatan')
        ->assertSee('Jaga kesehatan, jaga masa depan.')
        ->assertSeeHtml('images/display-tv/handwashing-hero.png')
        ->html();

    expect(substr_count($html, 'display-tv-ticker-content'))->toBe(2);
});

it('shows the configured advertisement video when it is enabled', function () {
    app(DisplayTvSettingsService::class)->save([
        'video_enabled' => true,
        'video_source' => 'youtube',
        'video_url' => 'https://www.youtube.com/embed/videoseries?list=PLtest123456789',
        'video_muted' => true,
        'running_text_enabled' => false,
        'running_text' => '',
    ]);

    Livewire::test('display.queue-board')
        ->assertSeeHtml('display-tv-ad-panel')
        ->assertSeeHtml('display-tv-video-media')
        ->assertDontSeeHtml('images/display-tv/handwashing-hero.png');
});

it('ducks video audio while keeping queue announcements clear', function () {
    app(DisplayTvSettingsService::class)->save([
        'video_enabled' => true,
        'video_source' => 'youtube',
        'video_url' => 'https://www.youtube.com/embed/videoseries?list=PLtest123456789',
        'video_muted' => false,
        'video_volume' => 28,
        'video_duck_volume' => 3,
        'running_text_enabled' => false,
        'running_text' => '',
    ]);

    Livewire::test('display.queue-board')
        ->assertSeeHtml('data-video-volume="28"')
        ->assertSeeHtml('data-video-duck-volume="3"')
        ->assertSee('Aktifkan suara TV');

    $javascript = file_get_contents(resource_path('js/app.js'));

    expect($javascript)
        ->toContain('const duckDisplayVideo = async () =>')
        ->toContain("configuredVideoVolume(video, 'data-video-duck-volume', 4)")
        ->toContain('fadeVideoVolume(video, duckVolume, 220)')
        ->toContain('fadeVideoVolume(video, normalVolume, 550)')
        ->toContain('syncDisplayVideoSoundState')
        ->toContain("video.dataset.dtvAutoMuted = '1'")
        ->toContain('video.loop = false')
        ->toContain('this.currentIndex = this.mediaUrls.length > 1')
        ->toContain("window.addEventListener('focus', this.focusSyncHandler)")
        ->toContain("document.visibilityState !== 'visible'")
        ->toContain("postVideoCommand(iframe, 'setVolume', [duckVolume])")
        ->toContain('const videoSoundPromise = enableDisplayVideoSound()')
        ->toContain("dispatchDisplayEvent('display-video-sound-enabled')")
        ->not->toContain('const muteDisplayVideo =');
});

it('shows the default health photo when advertisement video is disabled', function () {
    app(DisplayTvSettingsService::class)->save([
        'video_enabled' => false,
        'video_source' => 'youtube',
        'video_url' => 'https://www.youtube.com/embed/videoseries?list=PLtest123456789',
        'video_muted' => true,
        'running_text_enabled' => false,
        'running_text' => '',
    ]);

    Livewire::test('display.queue-board')
        ->assertSeeHtml('images/display-tv/handwashing-hero.png')
        ->assertDontSeeHtml('display-tv-ad-panel');
});

it('uses the primary now-serving card without a separate call overlay', function () {
    Livewire::test('display.queue-board')
        ->assertSeeHtml('data-queue-realtime="display"')
        ->assertSeeHtml('display-tv-now-serving')
        ->assertDontSeeHtml('display-tv-call-overlay');

    $javascript = file_get_contents(resource_path('js/app.js'));
    $referenceCss = file_get_contents(resource_path('css/display-tv-reference.css'));

    expect($javascript)
        ->not->toContain("Alpine.store('displayCall'")
        ->not->toContain('beginCall(')
        ->and($referenceCss)
        ->not->toContain('.display-tv-call-overlay');
});

it('subscribes realtime queue clients through a stable page marker', function () {
    $javascript = file_get_contents(resource_path('js/echo.js'));

    expect($javascript)
        ->toContain("document.querySelector('[data-queue-realtime]')")
        ->toContain("document.querySelector('[data-queue-realtime=\"display\"]')")
        ->toContain(".listen('.display.updated'")
        ->toContain("new CustomEvent('queue-display-announcement'")
        ->toContain('window.__queueRealtimeState')
        ->toContain('lifecycleListenersBound')
        ->toContain('call_id: announcement.call_id')
        ->not->toContain("window.Livewire.dispatch('play-display-announcement'")
        ->not->toContain('[wire\\\\:name="display.queue-board"]');
});

it('queues natural announcements without interrupting the active voice', function () {
    $javascript = file_get_contents(resource_path('js/app.js'));
    $pronunciation = file_get_contents(resource_path('js/tts/ticket-pronunciation.js'));

    expect($javascript)
        ->toContain('const announcementQueue = []')
        ->toContain('announcementQueue.sort(compareAnnouncements)')
        ->toContain("dispatchDisplayEvent('display-announcement-started', announcement)")
        ->toContain('window.__queueDisplayAnnouncementListenerState')
        ->toContain("window.addEventListener('queue-display-announcement'")
        ->not->toContain("Livewire.on('play-announcement'")
        ->toContain("openingAudio: createAnnouncementAudio('/sounds/opening.mp3')")
        ->toContain("closingAudio: createAnnouncementAudio('/sounds/closing.mp3')")
        ->toContain("from './tts/offline-tts'")
        ->toContain('synthesizeOfflineTts(speechSegments, settings.tts_speed, settings.tts_style)')
        ->toContain('TTS offline gagal, beralih ke TTS online browser.')
        ->toContain('TTS online gagal, beralih ke TTS offline lokal.')
        ->toContain('new SpeechSynthesisUtterance(message)')
        ->toContain('scoreOnlineTtsVoice')
        ->toContain('speak_zero_digits: toBoolean(settings.speak_zero_digits, true)')
        ->toContain(".join(', ')")
        ->toContain('pauseAfterMs: index === ticketCharacters.length - 1 ? 320 : 240')
        ->toContain('speedMultiplier: 0.82')
        ->toContain("voice_mode: 'offline_tts'")
        ->toContain("settings.voice_mode === 'online_tts'")
        ->not->toContain('offlineAnnouncementFiles')
        ->not->toContain('identifierAudioFiles')
        ->not->toContain('naturalNumberAudioFiles')
        ->not->toContain("queueLabel: 'antrian.wav'")
        ->toContain('settings.repeat_count')
        ->toContain('settings.repeat_pause_ms');

    expect($pronunciation)
        ->toContain("0: 'kosong'")
        ->toContain("B: 'b'")
        ->toContain(".filter((character) => speakZeroDigits || character !== '0')");

    expect(public_path('sounds/opening.mp3'))->toBeFile()
        ->and(public_path('sounds/closing.mp3'))->toBeFile();
});

it('supports one-click display kiosk audio autostart', function () {
    $javascript = file_get_contents(resource_path('js/app.js'));

    expect($javascript)
        ->toContain(".get('autostart') === '1'")
        ->toContain('window.__displayAudioUnlocked = displayKioskAutostartEnabled')
        ->toContain('if (displayKioskAutostartEnabled)')
        ->toContain('this.unlock();');
});

it('renders only one current-call card when two counters call close together', function () {
    $service = Service::factory()->create(['code' => 'M', 'name' => 'Poli Mata']);
    $firstCounter = Counter::factory()->create(['name' => 'Loket 1']);
    $secondCounter = Counter::factory()->create(['name' => 'Loket 2']);
    $firstCounter->services()->attach($service);
    $secondCounter->services()->attach($service);

    $manager = app(QueueManager::class);
    $manager->issueFromKiosk($service);
    $manager->issueFromKiosk($service);
    $manager->callNext($firstCounter);
    $manager->callNext($secondCounter);

    $html = Livewire::test('display.queue-board')->html();

    expect(substr_count($html, 'display-tv-now-call--active'))->toBe(1)
        ->and($html)->toContain('x-data="displayTvNowServing(')
        ->and($html)->toContain('wire:ignore')
        ->and($html)->not->toContain('display-tv-now-body--grid');
});

it('keeps the running text animation active when reduced motion is enabled', function () {
    $css = file_get_contents(resource_path('css/app.css'));

    expect($css)
        ->toContain('.display-tv-ticker-track')
        ->toContain('animation-duration: var(--display-tv-ticker-duration, 60s) !important')
        ->toContain('animation-iteration-count: infinite !important');
});

it('uses a measured ticker speed instead of a fixed duration', function () {
    Livewire::test('display.queue-board')
        ->assertSeeHtml('x-data="displayTvTicker()"')
        ->assertSeeHtml('wire:ignore');

    $javascript = file_get_contents(resource_path('js/app.js'));

    expect($javascript)
        ->toContain('pixelsPerSecond: 35')
        ->toContain('--display-tv-ticker-duration');

    $referenceCss = file_get_contents(resource_path('css/display-tv-reference.css'));
    $queueCss = file_get_contents(resource_path('css/queue-ui.css'));

    expect($referenceCss)
        ->toContain('font-size: clamp(1.05rem, 1.38vw, 1.78rem)')
        ->toContain('grid-template-rows: 9.4vh minmax(0, 1fr) 8.2vh')
        ->and($queueCss)
        ->toContain('grid-template-rows: 8.5vh minmax(0, 1fr) 17vh 7vh')
        ->toContain('font-size: clamp(1.05rem, 1.38vw, 1.78rem)');
});

it('renders the current queue number with clear character spacing', function () {
    $service = Service::factory()->create(['code' => 'Q', 'name' => 'Poli Umum']);
    $counter = Counter::factory()->create(['name' => 'Ruang Periksa']);
    $counter->services()->attach($service);

    $manager = app(QueueManager::class);
    $ticket = $manager->issueFromKiosk($service)->ticket;
    $manager->callNext($counter);

    Livewire::test('display.queue-board')
        ->assertSeeHtml('display-tv-now-code')
        ->assertSeeHtml('display-tv-now-separator')
        ->assertSeeHtml('display-tv-now-number')
        ->assertSee('Q')
        ->assertSee(substr($ticket->ticket_number, 1));

    $referenceCss = file_get_contents(resource_path('css/display-tv-reference.css'));

    expect($referenceCss)
        ->toContain('column-gap: .1em')
        ->toContain('letter-spacing: .01em');
});

it('shows completed calls in the recent strip ordered by latest first', function () {
    $service = Service::factory()->create(['code' => 'C', 'name' => 'Loket C']);
    $counterOne = Counter::factory()->create(['name' => 'Loket 1']);
    $counterTwo = Counter::factory()->create(['name' => 'Loket 2']);
    $counterOne->services()->attach($service);
    $counterTwo->services()->attach($service);

    $manager = app(QueueManager::class);
    $first = $manager->issueFromKiosk($service)->ticket;
    $second = $manager->issueFromKiosk($service)->ticket;

    $firstCall = $manager->callNext($counterOne);
    $manager->complete($manager->startServing($firstCall->ticket));

    $secondCall = $manager->callNext($counterTwo);
    $manager->complete($manager->startServing($secondCall->ticket));

    $board = $manager->displayBoard()->toArray();

    expect($board['now_serving'])->toHaveCount(0)
        ->and($board['recent_calls'])->toHaveCount(2)
        ->and(collect($board['recent_calls'])->pluck('ticket_number')->all())
        ->toContain($first->ticket_number, $second->ticket_number)
        ->and($board['recent_calls'][0]['ticket_number'])->toBe($second->ticket_number);
});

it('shows four placeholder cards when no calls have been completed', function () {
    $service = Service::factory()->create(['code' => 'D']);
    Counter::factory()->create()->services()->attach($service);

    $manager = app(QueueManager::class);
    $manager->issueFromKiosk($service);

    Livewire::test('display.queue-board')
        ->assertSeeHtml('display-tv-recent-flow')
        ->assertSeeHtml('display-tv-recent-card--idle')
        ->assertSee('Belum ada panggilan sebelumnya')
        ->assertDontSee('Silakan menunggu');
});

it('pads recent strip with empty cards up to four slots after a completed call', function () {
    $service = Service::factory()->create(['code' => 'F', 'name' => 'Farmasi']);
    $counter = Counter::factory()->create(['name' => 'Loket 3']);
    $counter->services()->attach($service);

    $manager = app(QueueManager::class);
    $ticket = $manager->issueFromKiosk($service)->ticket;
    $called = $manager->callNext($counter);
    $manager->complete($manager->startServing($called->ticket));

    $html = Livewire::test('display.queue-board')->html();

    preg_match_all('/wire:key="recent-call-[^"]+"/', $html, $recentCardKeys);

    expect(count($recentCardKeys[0]))->toBe(4)
        ->and(substr_count($html, 'display-tv-recent-card--idle'))->toBe(3)
        ->and($html)->toContain('data-recent-ticket="'.$ticket->ticket_number.'"')
        ->and($html)->toContain('Selesai');
});

it('includes completed recent calls in display board payload', function () {
    $service = Service::factory()->create(['code' => 'E', 'name' => 'Pendaftaran']);
    $counter = Counter::factory()->create(['name' => 'Loket 1']);
    $counter->services()->attach($service);

    $manager = app(QueueManager::class);
    $manager->issueFromKiosk($service);
    $called = $manager->callNext($counter);
    $manager->complete($manager->startServing($called->ticket));

    $payload = $manager->displayBoard()->toArray();

    expect($payload)->toHaveKey('recent_calls')
        ->and($payload['recent_calls'])->toHaveCount(1)
        ->and($payload['recent_calls'][0]['called_at'])->not->toBeNull()
        ->and($payload['recent_calls'][0]['status'])->toBe('completed');
});

it('does not render waiting upcoming cards on the display board', function () {
    $service = Service::factory()->create(['code' => 'G', 'name' => 'Laboratorium']);
    $counter = Counter::factory()->create(['name' => 'Loket Lab']);
    $counter->services()->attach($service);

    $manager = app(QueueManager::class);
    $manager->issueFromKiosk($service);
    $waiting = $manager->issueFromKiosk($service)->ticket;
    $manager->callNext($counter);

    Livewire::test('display.queue-board')
        ->assertDontSee('Antrian Berikutnya')
        ->assertDontSeeHtml('display-tv-upcoming-flow')
        ->assertDontSee($waiting->ticket_number);
});

it('refreshes board cards when queue-updated is dispatched', function () {
    $service = Service::factory()->create(['code' => 'R', 'name' => 'Radiologi']);
    $counter = Counter::factory()->create(['name' => 'Loket R']);
    $counter->services()->attach($service);

    $manager = app(QueueManager::class);
    $ticket = $manager->issueFromKiosk($service)->ticket;

    $component = Livewire::test('display.queue-board')
        ->assertDontSee($ticket->ticket_number);

    $manager->callNext($counter);

    $component
        ->dispatch('queue-updated')
        ->assertDispatched('display-board-synced')
        ->assertSee($ticket->ticket_number)
        ->assertSee('Loket R')
        ->assertSee('Sedang Dipanggil');
});

it('keeps realtime and refreshed card ordering aligned to the latest call id', function () {
    $service = Service::factory()->create(['code' => 'S', 'name' => 'Spesialis']);
    $firstCounter = Counter::factory()->create(['name' => 'Loket 1']);
    $secondCounter = Counter::factory()->create(['name' => 'Loket 2']);
    $firstCounter->services()->attach($service);
    $secondCounter->services()->attach($service);

    $manager = app(QueueManager::class);
    $firstTicket = $manager->issueFromKiosk($service)->ticket;
    $secondTicket = $manager->issueFromKiosk($service)->ticket;
    $firstCall = $manager->callNext($firstCounter);
    $manager->callNext($secondCounter);
    $latestCall = $manager->recall($firstCall->ticket, $firstCounter);

    $board = $manager->displayBoard()->toArray();

    expect($board['now_serving'][0])
        ->toMatchArray([
            'ticket_number' => $firstTicket->ticket_number,
            'call_id' => $latestCall->call->id,
        ])
        ->and($board['now_serving'][1]['ticket_number'])->toBe($secondTicket->ticket_number);

    Livewire::test('display.queue-board')
        ->dispatch('queue-updated')
        ->assertDispatched('display-board-synced', call: $board['now_serving'][0])
        ->assertSee($firstTicket->ticket_number);

    $javascript = file_get_contents(resource_path('js/app.js'));

    expect($javascript)
        ->toContain('syncedCall.call_id < this.currentCall.call_id')
        ->toContain('latestDisplayAnnouncement');
});

it('keeps the newest active call in now serving while prior completed calls fill recent', function () {
    $service = Service::factory()->create(['code' => 'H', 'name' => 'Poli Umum']);
    $counter = Counter::factory()->create(['name' => 'Loket 1']);
    $counter->services()->attach($service);

    $manager = app(QueueManager::class);
    $first = $manager->issueFromKiosk($service)->ticket;
    $second = $manager->issueFromKiosk($service)->ticket;

    $firstCall = $manager->callNext($counter);
    $manager->complete($manager->startServing($firstCall->ticket));
    $manager->callNext($counter);

    $board = $manager->displayBoard()->toArray();

    expect($board['now_serving'])->toHaveCount(1)
        ->and($board['now_serving'][0]['ticket_number'])->toBe($second->ticket_number)
        ->and($board['recent_calls'])->toHaveCount(1)
        ->and($board['recent_calls'][0]['ticket_number'])->toBe($first->ticket_number);
});
