<?php

namespace App\Http\Requests\Sdmk;

use App\Enums\Hari;
use App\Enums\SdmkReferenceType as T;
use App\Enums\StatusJadwal;
use App\Http\Requests\Concerns\ChecksReference;
use Illuminate\Validation\Rule;

class SlotRequest extends SdmkRequest
{
    use ChecksReference;

    public function rules(): array
    {
        $istirahat = filter_var($this->input('adaIstirahat'), FILTER_VALIDATE_BOOLEAN);

        return [
            'hari' => ['required', Rule::enum(Hari::class)],
            'jamMulai' => ['required', 'regex:' . self::REGEX_JAM],
            'jamSelesai' => ['required', 'regex:' . self::REGEX_JAM, 'after:jamMulai'],
            'adaIstirahat' => ['required', 'boolean'],
            'istirahatMulai' => [$istirahat ? 'required' : 'nullable', 'regex:' . self::REGEX_JAM, 'after_or_equal:jamMulai'],
            'istirahatSelesai' => [$istirahat ? 'required' : 'nullable', 'regex:' . self::REGEX_JAM, 'after:istirahatMulai', 'before_or_equal:jamSelesai'],
            'durasiLayanan' => ['required', Rule::in(array_map('strval', config('sdmk.durasi_layanan')))],
            'kuota' => ['required', 'integer', 'min:1', 'max:1000'],
            'kuotaOnline' => ['required', 'integer', 'min:0', 'lte:kuota'],
            'jenisPasien' => ['required', 'array', 'min:1'],
            'jenisPasien.*' => ['string', $this->refRule(T::JenisPasien)],
            'berlakuMulai' => ['required', 'date_format:Y-m-d'],
            'berlakuHingga' => ['nullable', 'date_format:Y-m-d', 'after:berlakuMulai'],
            'pola' => ['required', $this->refRule(T::PolaJadwal)],
            'status' => ['required', Rule::enum(StatusJadwal::class)],
        ];
    }

    public function messages(): array
    {
        return [...parent::messages(), 'lte' => ':attribute tidak boleh melebihi kuota.', 'before_or_equal' => ':attribute di luar jam praktik.'];
    }

    public function attributes(): array
    {
        return ['jamMulai' => 'Jam mulai', 'jamSelesai' => 'Jam selesai', 'istirahatMulai' => 'Mulai istirahat',
            'istirahatSelesai' => 'Selesai istirahat', 'durasiLayanan' => 'Durasi layanan', 'kuota' => 'Kuota',
            'kuotaOnline' => 'Kuota online', 'jenisPasien' => 'Jenis pasien', 'berlakuMulai' => 'Berlaku mulai',
            'berlakuHingga' => 'Berlaku hingga', 'pola' => 'Pola'];
    }

    /** @return array<string, mixed> */
    public function attrs(): array
    {
        $v = $this->validated();
        $break = filter_var($v['adaIstirahat'], FILTER_VALIDATE_BOOLEAN);

        return [
            'hari' => $v['hari'], 'jam_mulai' => $v['jamMulai'], 'jam_selesai' => $v['jamSelesai'],
            'istirahat_mulai' => $break ? $v['istirahatMulai'] : null, 'istirahat_selesai' => $break ? $v['istirahatSelesai'] : null,
            'durasi_layanan' => (int) $v['durasiLayanan'], 'kuota' => (int) $v['kuota'], 'kuota_online' => (int) $v['kuotaOnline'],
            'jenis_pasien' => array_values($v['jenisPasien']), 'berlaku_mulai' => $v['berlakuMulai'],
            'berlaku_hingga' => $v['berlakuHingga'] ?? null, 'pola' => $v['pola'], 'status' => $v['status'],
        ];
    }
}
