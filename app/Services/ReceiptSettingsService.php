<?php

namespace App\Services;

class ReceiptSettingsService
{
    public const KEY_SETTINGS = 'receipt_print_settings';

    /**
     * @var array<string, mixed>
     */
    private const DEFAULTS = [
        'show_institution_name' => true,
        'show_institution_address' => false,
        'show_institution_phone' => false,
        'show_receipt_title' => true,
        'show_ticket_label' => true,
        'show_service_name' => true,
        'show_service_code' => true,
        'show_estimated_wait' => true,
        'show_waiting_count' => true,
        'show_queue_date' => true,
        'show_issued_time' => true,
        'show_footer' => true,
        'receipt_title_label' => 'TIKET ANTRIAN',
        'ticket_number_label' => 'NOMOR ANTRIAN',
        'service_name_label' => 'Layanan',
        'service_code_label' => 'Kode',
        'estimated_wait_label' => 'Estimasi',
        'waiting_count_label' => 'Antrian',
        'queue_date_label' => 'Tanggal',
        'issued_time_label' => 'Jam',
        'estimated_wait_suffix' => 'menit',
        'waiting_count_suffix' => 'orang',
        'footer_line_1' => 'Harap menunggu panggilan',
        'footer_line_2' => 'di monitor ruang tunggu',
        'thank_you_label' => 'Terima kasih',
    ];

    public function __construct(private SettingsService $settings) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $stored = $this->settings->get(self::KEY_SETTINGS, []);

        if (is_array($stored) && isset($stored['value']) && is_array($stored['value'])) {
            $stored = $stored['value'];
        }

        if (! is_array($stored)) {
            $stored = [];
        }

        return array_replace(self::DEFAULTS, array_intersect_key($stored, self::DEFAULTS));
    }

    /**
     * @return array<string, mixed>
     */
    public function toFormState(): array
    {
        return $this->toArray();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function save(array $data): void
    {
        $payload = [];

        foreach (self::DEFAULTS as $key => $default) {
            $value = $data[$key] ?? $default;
            $payload[$key] = is_bool($default)
                ? (bool) $value
                : trim((string) $value);
        }

        $this->settings->set(self::KEY_SETTINGS, ['value' => $payload], 'receipt');
    }

    /**
     * @return array<string, mixed>
     */
    public function printerOptions(): array
    {
        return $this->toArray();
    }
}
