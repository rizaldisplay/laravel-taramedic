<?php

namespace Database\Seeders;

use App\Models\AnnouncementConfig;
use App\Models\Antrean;
use App\Models\Counter;
use App\Models\Service;
use App\Models\Setting;
use App\Services\DisplayTvSettingsService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QueueSystemSeeder extends Seeder
{
    public function run(): void
    {
        $services = $this->seedServices();
        $counters = $this->seedCounters();
        $antreans = $this->seedAntreans();

        $this->assignServicesToCounters(
            services: $services,
            counters: $counters,
            antreans: $antreans,
        );

        $this->seedDisplayTvSettings();
        $this->seedAnnouncementConfig();
        $this->seedSettings();
    }

    /**
     * Service menentukan jenis nomor antrean.
     *
     * A = Pasien Baru
     * B = Pasien Lama
     *
     * @return array<string, Service>
     */
    private function seedServices(): array
    {
        $definitions = [
            [
                'code' => 'A',
                'name' => 'Pasien Baru',
                'estimated_minutes' => 12,
                'sort_order' => 1,
            ],
            [
                'code' => 'B',
                'name' => 'Pasien Lama',
                'estimated_minutes' => 5,
                'sort_order' => 2,
            ],
        ];

        $services = [];

        foreach ($definitions as $definition) {
            $services[$definition['code']] =
                Service::query()->updateOrCreate(
                    ['code' => $definition['code']],
                    [
                        ...$definition,
                        'is_active' => true,
                    ]
                );
        }

        return $services;
    }

    /**
     * Counter menentukan lokasi/tahapan pelayanan.
     *
     * @return array<string, Counter>
     */
    private function seedCounters(): array
    {
        $definitions = [
            [
                'code' => 'P',
                'name' => 'Loket Pendaftaran',
                'sort_order' => 1,
            ],
            [
                'code' => 'R',
                'name' => 'Ruang Poli',
                'sort_order' => 2,
            ],
            [
                'code' => 'F',
                'name' => 'Loket Farmasi',
                'sort_order' => 3,
            ],
            [
                'code' => 'K',
                'name' => 'Loket Kasir',
                'sort_order' => 4,
            ],
        ];

        $counters = [];

        foreach ($definitions as $definition) {
            $counters[$definition['code']] =
                Counter::query()->updateOrCreate(
                    ['code' => $definition['code']],
                    [
                        ...$definition,
                        'is_active' => true,
                    ]
                );
        }

        return $counters;
    }

    /**
     * Antrean menentukan loket fisik.
     *
     * @return array<string, Antrean>
     */
    private function seedAntreans(): array
    {
        $definitions = [
            [
                'code' => 'L001',
                'name' => 'Loket 1',
                'sort_order' => 1,
            ],
            [
                'code' => 'L002',
                'name' => 'Loket 2',
                'sort_order' => 2,
            ],
            [
                'code' => 'L003',
                'name' => 'Loket 3',
                'sort_order' => 3,
            ],
            [
                'code' => 'L004',
                'name' => 'Loket 4',
                'sort_order' => 4,
            ],
        ];

        $antreans = [];

        foreach ($definitions as $definition) {
            $antreans[$definition['code']] =
                Antrean::query()->updateOrCreate(
                    ['code' => $definition['code']],
                    [
                        ...$definition,
                        'is_active' => true,
                    ]
                );
        }

        return $antreans;
    }

    /**
     * Menghubungkan setiap counter dengan seluruh service
     * dan antrean yang tersedia.
     *
     * @param array<string, Service> $services
     * @param array<string, Counter> $counters
     * @param array<string, Antrean> $antreans
     */
    private function assignServicesToCounters(
        array $services,
        array $counters,
        array $antreans,
    ): void {
        $now = now();

        $rows = [];

        foreach ($counters as $counter) {
            foreach ($services as $service) {
                foreach ($antreans as $antrean) {
                    $rows[] = [
                        'counter_id' => $counter->id,
                        'service_id' => $service->id,
                        'antrean_id' => $antrean->id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        DB::table('counter_service')->upsert(
            $rows,
            [
                'counter_id',
                'service_id',
                'antrean_id',
            ],
            [
                'updated_at',
            ]
        );
    }

    /**
     * Pengaturan Display TV.
     */
    private function seedDisplayTvSettings(): void
    {
        app(DisplayTvSettingsService::class)->save([
            'video_enabled' => false,
            'video_source' => 'url',
            'video_url' => '',
            'running_text_enabled' => true,
            'running_text' =>
                'Harap menunggu hingga nomor antrean Anda dipanggil.',
        ]);
    }

    /**
     * Konfigurasi pengumuman suara.
     */
    private function seedAnnouncementConfig(): void
    {
        AnnouncementConfig::query()->firstOrCreate(
            [],
            [
                'tts_template' =>
                    config('queue_system.default_tts_template'),
            ]
        );
    }

    /**
     * Pengaturan aplikasi.
     */
    private function seedSettings(): void
    {
        $settings = [
            [
                'key' => 'institution_name',
                'group' => 'general',
                'value' => [
                    'value' => 'Klinik Pratama Mitra Sehat',
                ],
            ],
            [
                'key' => 'institution_tagline',
                'group' => 'general',
                'value' => [
                    'value' =>
                        'Pelayanan klinik yang mudah, cepat, dan tertib',
                ],
            ],
            [
                'key' => 'institution_address',
                'group' => 'general',
                'value' => [
                    'value' => '',
                ],
            ],
            [
                'key' => 'institution_phone',
                'group' => 'general',
                'value' => [
                    'value' => '',
                ],
            ],
            [
                'key' => 'kiosk_welcome_message',
                'group' => 'kiosk',
                'value' => [
                    'value' =>
                        'Selamat datang. Silakan pilih jenis pasien untuk mengambil nomor antrean.',
                ],
            ],
        ];

        foreach ($settings as $setting) {
            Setting::query()->updateOrCreate(
                ['key' => $setting['key']],
                [
                    'group' => $setting['group'],
                    'value' => $setting['value'],
                ]
            );
        }
    }
}