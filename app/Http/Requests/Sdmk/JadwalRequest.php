<?php

namespace App\Http\Requests\Sdmk;

use App\Enums\Hari;
use App\Enums\SdmkReferenceType as T;
use App\Enums\StatusJadwal;
use App\Http\Requests\Concerns\ChecksReference;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** PUT /sdmk/{sdmk}/jadwal  { setting: {...}, hari: [7 baris] } */
class JadwalRequest extends SdmkRequest
{
    use ChecksReference;

    public function rules(): array
    {
        $dur = array_map('strval', config('sdmk.durasi_layanan'));

        return [
            'setting' => ['required', 'array'],
            'setting.tipeJadwal' => ['required', $this->refRule(T::TipeJadwal)],
            'setting.kapasitasPerHari' => ['required', 'integer', 'min:1', 'max:1000'],
            'setting.durasiLayanan' => ['required', Rule::in($dur)],
            'setting.jenisPasien' => ['required', 'array', 'min:1'],
            'setting.jenisPasien.*' => ['string', $this->refRule(T::JenisPasien)],
            'setting.status' => ['required', Rule::enum(StatusJadwal::class)],

            'hari' => ['required', 'array', 'size:7'],
            'hari.*.hari' => ['required', Rule::enum(Hari::class), 'distinct'],
            'hari.*.aktif' => ['required', 'boolean'],
            'hari.*.jamMulai' => ['nullable', 'required_if:hari.*.aktif,1,true', 'regex:' . self::REGEX_JAM],
            'hari.*.jamSelesai' => ['nullable', 'required_if:hari.*.aktif,1,true', 'regex:' . self::REGEX_JAM],
            'hari.*.durasiSlot' => ['nullable', 'integer', 'min:1', 'max:240'],
            'hari.*.kuota' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'hari.*.kuotaOnline' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'hari.*.istirahatMulai' => ['nullable', 'regex:' . self::REGEX_JAM],
            'hari.*.istirahatSelesai' => ['nullable', 'regex:' . self::REGEX_JAM],
            'hari.*.jenisPasien' => ['nullable', 'array'],
            'hari.*.jenisPasien.*' => ['string', $this->refRule(T::JenisPasien)],
            'hari.*.status' => ['required', Rule::enum(StatusJadwal::class)],
        ];
    }

    /**
     * Kunci error disesuaikan dengan form frontend:
     *  setting.tipeJadwal -> tipeJadwal;  hari.3.jamMulai -> hari.Kamis
     */
    protected function failedValidation(Validator $validator): void
    {
        $days = array_column($this->input('hari', []), 'hari');
        $errors = [];

        foreach ($validator->errors()->messages() as $key => $messages) {
            if (preg_match('/^hari\.(\d+)(\.|$)/', $key, $m)) {
                $key = 'hari.' . ($days[(int) $m[1]] ?? $m[1]);
            } elseif (str_starts_with($key, 'setting.')) {
                $key = explode('.', $key)[1];
            }
            $errors[$key] ??= $messages;
        }

        throw new ValidationException($validator, new JsonResponse([
            'message' => 'Jadwal belum lengkap. Periksa kolom yang ditandai merah.',
            'errors' => $errors,
        ], 422));
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            foreach ($this->input('hari', []) as $i => $r) {
                if (! filter_var($r['aktif'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                    continue;
                }
                if (($r['jamSelesai'] ?? '') <= ($r['jamMulai'] ?? '')) {
                    $v->errors()->add("hari.$i.jamSelesai", 'Jam selesai harus setelah jam mulai.');
                }
                if (! isset($r['kuota'])) {
                    $v->errors()->add("hari.$i.kuota", 'Kuota wajib diisi untuk hari aktif.');
                } elseif (isset($r['kuotaOnline']) && (int) $r['kuotaOnline'] > (int) $r['kuota']) {
                    $v->errors()->add("hari.$i.kuotaOnline", 'Kuota online melebihi kuota.');
                }
            }
        });
    }

    public function attributes(): array
    {
        return ['setting.tipeJadwal' => 'Tipe jadwal', 'setting.kapasitasPerHari' => 'Kapasitas per hari',
            'setting.durasiLayanan' => 'Durasi layanan', 'setting.jenisPasien' => 'Jenis pasien', 'setting.status' => 'Status jadwal',
            'hari' => 'Jadwal hari', 'hari.*.jamMulai' => 'Jam mulai', 'hari.*.jamSelesai' => 'Jam selesai',
            'hari.*.durasiSlot' => 'Durasi slot', 'hari.*.kuota' => 'Kuota', 'hari.*.kuotaOnline' => 'Kuota online'];
    }

    /** @return array<string, mixed> kolom schedule_* pada health_workers */
    public function settingAttrs(): array
    {
        $s = $this->validated()['setting'];

        return [
            'schedule_type' => $s['tipeJadwal'], 'daily_capacity' => (int) $s['kapasitasPerHari'],
            'service_duration' => (int) $s['durasiLayanan'], 'schedule_patient_types' => array_values($s['jenisPasien']),
            'schedule_status' => $s['status'],
        ];
    }

    /** @return list<array<string, mixed>> baris worker_schedule_days */
    public function dayRows(): array
    {
        return array_map(function (array $r) {
            $aktif = filter_var($r['aktif'], FILTER_VALIDATE_BOOLEAN);

            return [
                'hari' => $r['hari'], 'aktif' => $aktif,
                'jam_mulai' => $aktif ? ($r['jamMulai'] ?? null) : null,
                'jam_selesai' => $aktif ? ($r['jamSelesai'] ?? null) : null,
                'durasi_slot' => $r['durasiSlot'] ?? null, 'kuota' => $r['kuota'] ?? null, 'kuota_online' => $r['kuotaOnline'] ?? null,
                'istirahat_mulai' => $r['istirahatMulai'] ?? null, 'istirahat_selesai' => $r['istirahatSelesai'] ?? null,
                'jenis_pasien' => array_values($r['jenisPasien'] ?? []), 'status' => $r['status'],
            ];
        }, $this->validated()['hari']);
    }
}
