<?php

namespace Database\Seeders;

use App\Models\AnnouncementConfig;
use App\Models\Counter;
use App\Models\Service;
use App\Models\Setting;
use App\Services\DisplayTvSettingsService;
use Illuminate\Database\Seeder;

class QueueSystemSeeder extends Seeder
{
    public function run(): void
    {
        $services = $this->seedServices();
        $counters = $this->seedCounters();

        $counters['P']->services()->sync([$services['A']->id]);
        $counters['R']->services()->sync([$services['B']->id, $services['D']->id, $services['E']->id]);
        $counters['F']->services()->sync([$services['C']->id, $services['F']->id]);

        app(DisplayTvSettingsService::class)->save([
            'video_enabled' => false,
            'video_source' => 'url',
            'video_url' => '',
            'running_text_enabled' => true,
            'running_text' => 'Harap menunggu hingga nomor antrean Anda dipanggil.',
        ]);

        AnnouncementConfig::query()->firstOrCreate([], [
            'tts_template' => config('queue_system.default_tts_template'),
        ]);

        $this->seedSettings();
    }

    /**
     * @return array<string, Service>
     */
    private function seedServices(): array
    {
        $definitions = [
            ['code' => 'A', 'name' => 'Pendaftaran', 'estimated_minutes' => 5, 'sort_order' => 1],
            ['code' => 'B', 'name' => 'Poli Umum', 'estimated_minutes' => 12, 'sort_order' => 2],
            ['code' => 'C', 'name' => 'Farmasi', 'estimated_minutes' => 8, 'sort_order' => 3],
            ['code' => 'D', 'name' => 'Poli Gigi', 'estimated_minutes' => 12, 'sort_order' => 4],
            ['code' => 'E', 'name' => 'Konsultasi', 'estimated_minutes' => 20, 'sort_order' => 5],
            ['code' => 'F', 'name' => 'Laboratorium', 'estimated_minutes' => 10, 'sort_order' => 6],
        ];

        $services = [];

        foreach ($definitions as $definition) {
            $services[$definition['code']] = Service::query()->updateOrCreate(
                ['code' => $definition['code']],
                [...$definition, 'is_active' => true],
            );
        }

        return $services;
    }

    /**
     * @return array<string, Counter>
     */
    private function seedCounters(): array
    {
        $definitions = [
            ['code' => 'P', 'name' => 'Loket Pendaftaran', 'sort_order' => 1],
            ['code' => 'R', 'name' => 'Ruang Periksa', 'sort_order' => 2],
            ['code' => 'F', 'name' => 'Loket Farmasi', 'sort_order' => 3],
        ];

        $counters = [];

        foreach ($definitions as $definition) {
            $counters[$definition['code']] = Counter::query()->updateOrCreate(
                ['code' => $definition['code']],
                [...$definition, 'is_active' => true],
            );
        }

        return $counters;
    }

    private function seedSettings(): void
    {
        $settings = [
            ['key' => 'institution_name', 'group' => 'general', 'value' => ['value' => 'Klinik Pratama Mitra Sehat']],
            ['key' => 'institution_tagline', 'group' => 'general', 'value' => ['value' => 'Pelayanan antrean klinik yang mudah dan tertib']],
            ['key' => 'institution_address', 'group' => 'general', 'value' => ['value' => '']],
            ['key' => 'institution_phone', 'group' => 'general', 'value' => ['value' => '']],
            ['key' => 'kiosk_welcome_message', 'group' => 'kiosk', 'value' => ['value' => 'Selamat datang. Silakan pilih layanan untuk mengambil nomor antrean.']],
        ];

        foreach ($settings as $setting) {
            Setting::query()->updateOrCreate(
                ['key' => $setting['key']],
                ['group' => $setting['group'], 'value' => $setting['value']],
            );
        }
    }
}
