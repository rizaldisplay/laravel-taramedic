<?php

namespace App\Services;

use App\Models\AnnouncementConfig;

class AnnouncementSettingsService
{
    /**
     * @return array<string, mixed>
     */
    public function formState(): array
    {
        $config = AnnouncementConfig::query()->firstOrNew();

        return [
            'voice_mode' => $config->voice_mode->value,
            'announcement_enabled' => $config->announcement_enabled,
            'audio_volume' => $config->audio_volume,
            'repeat_count' => $config->repeat_count,
            'opening_enabled' => $config->opening_enabled,
            'closing_enabled' => $config->closing_enabled,
            'cue_gap_ms' => $config->cue_gap_ms,
            'repeat_pause_ms' => $config->repeat_pause_ms,
            'tts_template' => $config->tts_template ?? config('queue_system.default_tts_template'),
            'speak_zero_digits' => $config->speak_zero_digits,
            'tts_speed' => (float) $config->tts_speed,
            'tts_style' => $config->tts_style->value,
            'online_pitch' => (float) $config->online_pitch,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function save(array $data): AnnouncementConfig
    {
        $config = AnnouncementConfig::query()->firstOrNew();
        $config->fill($data);
        $config->save();

        return $config;
    }

    /**
     * @return array<string, mixed>
     */
    public function frontendConfig(): array
    {
        $settings = $this->formState();
        unset($settings['tts_template']);

        return $settings;
    }
}
