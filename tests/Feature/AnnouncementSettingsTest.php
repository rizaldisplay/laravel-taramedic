<?php

use App\Enums\AnnouncementVoiceMode;
use App\Enums\AnnouncementVoiceStyle;
use App\Models\AnnouncementConfig;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('offers exactly local offline and browser online TTS modes', function () {
    $admin = User::factory()->admin()->create();

    expect(AnnouncementVoiceMode::cases())->toHaveCount(2);

    Livewire::actingAs($admin)
        ->test('admin.settings')
        ->set('tab', 'audio')
        ->assertSet('audio.voice_mode', AnnouncementVoiceMode::OfflineTts->value)
        ->assertSet('audio.tts_style', AnnouncementVoiceStyle::Natural->value)
        ->assertSet('audio.cue_gap_ms', 0)
        ->assertSet('audio.speak_zero_digits', true)
        ->assertSee('TTS Offline Lokal')
        ->assertSee('TTS Online Browser')
        ->assertSee('Sebutkan angka 0')
        ->assertDontSee('Aset suara offline')
        ->assertDontSee('Susunan aset offline');
});

it('locks the bundled voice to Indonesian without a manual language setting', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test('admin.settings')
        ->set('tab', 'audio')
        ->assertDontSee('Kode bahasa')
        ->assertDontSeeHtml('tts-language');

    expect(Schema::hasColumn('announcement_configs', 'tts_language'))->toBeFalse();
});

it('allows an administrator to configure offline TTS naturalness and timing', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test('admin.settings')
        ->set('audio', [
            'voice_mode' => AnnouncementVoiceMode::OfflineTts->value,
            'announcement_enabled' => true,
            'audio_volume' => 75,
            'repeat_count' => 2,
            'opening_enabled' => false,
            'closing_enabled' => false,
            'cue_gap_ms' => 0,
            'repeat_pause_ms' => 1200,
            'tts_template' => 'Nomor antrian {ticket_number}, silakan menuju loket {counter_name}.',
            'speak_zero_digits' => false,
            'tts_speed' => 0.9,
            'tts_style' => AnnouncementVoiceStyle::Expressive->value,
            'online_pitch' => 1,
        ])
        ->call('saveAudio')
        ->assertHasNoErrors();

    $config = AnnouncementConfig::query()->firstOrFail();

    expect($config->voice_mode)->toBe(AnnouncementVoiceMode::OfflineTts)
        ->and($config->announcement_enabled)->toBeTrue()
        ->and($config->audio_volume)->toBe(75)
        ->and($config->repeat_count)->toBe(2)
        ->and($config->opening_enabled)->toBeFalse()
        ->and($config->closing_enabled)->toBeFalse()
        ->and($config->cue_gap_ms)->toBe(0)
        ->and($config->repeat_pause_ms)->toBe(1200)
        ->and($config->speak_zero_digits)->toBeFalse()
        ->and((float) $config->tts_speed)->toBe(0.9)
        ->and($config->tts_style)->toBe(AnnouncementVoiceStyle::Expressive);
});

it('allows an administrator to select online TTS and pitch', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test('admin.settings')
        ->set('audio.voice_mode', AnnouncementVoiceMode::OnlineTts->value)
        ->set('audio.online_pitch', 1.15)
        ->call('saveAudio')
        ->assertHasNoErrors();

    $config = AnnouncementConfig::query()->firstOrFail();

    expect($config->voice_mode)->toBe(AnnouncementVoiceMode::OnlineTts)
        ->and((float) $config->online_pitch)->toBe(1.15);
});

it('rejects legacy audio modes and unsafe tuning values', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test('admin.settings')
        ->set('audio.audio_volume', 101)
        ->set('audio.repeat_count', 4)
        ->set('audio.cue_gap_ms', 501)
        ->set('audio.repeat_pause_ms', 5001)
        ->set('audio.speak_zero_digits', 'sometimes')
        ->set('audio.tts_style', 'dramatic')
        ->set('audio.online_pitch', 1.31)
        ->set('audio.voice_mode', 'offline')
        ->call('saveAudio')
        ->assertHasErrors([
            'audio.audio_volume',
            'audio.repeat_count',
            'audio.cue_gap_ms',
            'audio.repeat_pause_ms',
            'audio.speak_zero_digits',
            'audio.tts_style',
            'audio.online_pitch',
            'audio.voice_mode',
        ]);
});

it('exposes dual TTS settings to the display player', function () {
    AnnouncementConfig::query()->create([
        'voice_mode' => AnnouncementVoiceMode::OnlineTts,
        'audio_volume' => 65,
        'repeat_count' => 3,
        'cue_gap_ms' => 25,
        'repeat_pause_ms' => 900,
        'speak_zero_digits' => false,
        'tts_style' => AnnouncementVoiceStyle::Calm,
        'online_pitch' => 0.95,
    ]);

    Livewire::test('display.queue-board')
        ->assertSeeHtml('data-announcement-settings=')
        ->assertSeeHtml('&quot;voice_mode&quot;:&quot;online_tts&quot;')
        ->assertSeeHtml('&quot;audio_volume&quot;:65')
        ->assertSeeHtml('&quot;speak_zero_digits&quot;:false')
        ->assertSeeHtml('&quot;tts_style&quot;:&quot;calm&quot;')
        ->assertSeeHtml('&quot;online_pitch&quot;:0.95');
});

it('removes the legacy fragment audio configuration columns', function () {
    expect(Schema::hasColumn('announcement_configs', 'number_mode'))->toBeFalse()
        ->and(Schema::hasColumn('announcement_configs', 'queue_label_enabled'))->toBeFalse()
        ->and(Schema::hasColumn('announcement_configs', 'destination_enabled'))->toBeFalse()
        ->and(Schema::hasColumn('announcement_configs', 'counter_label_enabled'))->toBeFalse()
        ->and(Schema::hasColumn('announcement_configs', 'clip_pause_ms'))->toBeFalse()
        ->and(Schema::hasColumn('announcement_configs', 'cue_gap_ms'))->toBeTrue()
        ->and(Schema::hasColumn('announcement_configs', 'tts_language'))->toBeFalse()
        ->and(Schema::hasColumn('announcement_configs', 'speak_zero_digits'))->toBeTrue()
        ->and(Schema::hasColumn('announcement_configs', 'tts_style'))->toBeTrue()
        ->and(Schema::hasColumn('announcement_configs', 'online_pitch'))->toBeTrue();
});
