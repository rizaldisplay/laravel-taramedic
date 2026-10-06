<?php

namespace App\Models;

use App\Enums\AnnouncementVoiceMode;
use App\Enums\AnnouncementVoiceStyle;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'voice_mode',
    'announcement_enabled',
    'audio_volume',
    'repeat_count',
    'opening_enabled',
    'closing_enabled',
    'cue_gap_ms',
    'repeat_pause_ms',
    'tts_template',
    'speak_zero_digits',
    'tts_speed',
    'tts_style',
    'online_pitch',
])]
class AnnouncementConfig extends Model
{
    /** @var array<string, mixed> */
    protected $attributes = [
        'voice_mode' => AnnouncementVoiceMode::OfflineTts->value,
        'announcement_enabled' => true,
        'audio_volume' => 100,
        'repeat_count' => 1,
        'opening_enabled' => true,
        'closing_enabled' => true,
        'cue_gap_ms' => 0,
        'repeat_pause_ms' => 800,
        'speak_zero_digits' => true,
        'tts_speed' => 0.8,
        'tts_style' => AnnouncementVoiceStyle::Natural->value,
        'online_pitch' => 1,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'voice_mode' => AnnouncementVoiceMode::class,
            'announcement_enabled' => 'boolean',
            'audio_volume' => 'integer',
            'repeat_count' => 'integer',
            'opening_enabled' => 'boolean',
            'closing_enabled' => 'boolean',
            'cue_gap_ms' => 'integer',
            'repeat_pause_ms' => 'integer',
            'speak_zero_digits' => 'boolean',
            'tts_speed' => 'decimal:2',
            'tts_style' => AnnouncementVoiceStyle::class,
            'online_pitch' => 'decimal:2',
        ];
    }
}
