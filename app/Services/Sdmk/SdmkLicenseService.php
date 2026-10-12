<?php

namespace App\Services\Sdmk;

use App\Actions\Masterdata\Sdmk\RecomputeLicenseStatusAction;
use App\Actions\Masterdata\Sdmk\SaveSipAction;
use App\Events\SdmkActivityRecorded;
use App\Exceptions\SdmkRuleException;
use App\Models\HealthWorker;
use App\Models\WorkerCompetency;
use App\Models\WorkerSip;
use App\Services\RegionService;
use App\Support\SdmkFiles;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class SdmkLicenseService
{
    public function __construct(
        private readonly RecomputeLicenseStatusAction $recompute,
        private readonly SaveSipAction $saveSip,
        private readonly RegionService $regions,
    ) {
    }

    /** @param array<string, mixed> $attrs */
    public function saveStr(HealthWorker $worker, array $attrs, ?string $otherCertificates, ?int $userId): void
    {
        $numberChanged = $worker->str_number !== $attrs['str_number'];

        $worker->update([
            ...$attrs,
            'other_certificates' => $otherCertificates,
            // Mengubah nomor STR membatalkan verifikasi sebelumnya.
            ...($numberChanged ? ['str_verified_at' => null, 'str_verified_by' => null] : []),
            'updated_by' => $userId,
        ]);

        $this->recompute->execute($worker);
        SdmkActivityRecorded::dispatch($worker->id, 'riwayat', 'STR disimpan', null, $userId);
    }

    /**
     * Penandaan "sudah diverifikasi" oleh petugas. Tidak ada pengecekan otomatis ke KKI
     * (belum ada integrasi); sistem hanya mencatat siapa yang memverifikasi dan kapan.
     */
    public function verifyStr(HealthWorker $worker, ?int $userId): void
    {
        if (! $worker->str_number || ! $worker->str_expires_at) {
            throw new SdmkRuleException('nomorStr', 'Simpan data STR terlebih dahulu sebelum diverifikasi.');
        }
        if (Carbon::parse($worker->str_expires_at)->isPast()) {
            throw new SdmkRuleException('berlakuHingga', 'STR sudah kedaluwarsa dan tidak dapat diverifikasi.');
        }

        $worker->update(['str_verified_at' => now(), 'str_verified_by' => $userId]);
        SdmkActivityRecorded::dispatch($worker->id, 'audit', 'STR diverifikasi petugas', null, $userId);
    }

    /** @param array<string, mixed> $attrs kolom worker_sips tanpa nama wilayah */
    public function saveSip(HealthWorker $worker, array $attrs, ?UploadedFile $file, ?WorkerSip $sip, ?int $userId): WorkerSip
    {
        $names = $this->regions->resolveNames($attrs['province_code'], $attrs['regency_code'], $attrs['district_code'], $attrs['village_code']);

        return $this->saveSip->execute($worker, [...$attrs, ...$names], $file, $sip, $userId);
    }

    public function deleteSip(HealthWorker $worker, WorkerSip $sip, ?int $userId): void
    {
        $path = $sip->document_path;
        DB::transaction(fn () => $sip->delete());
        SdmkFiles::delete($path);

        $this->recompute->execute($worker);
        SdmkActivityRecorded::dispatch($worker->id, 'riwayat', 'SIP dihapus', $sip->jenis_sip, $userId);
    }

    /** @param array<string, mixed> $attrs */
    public function addCompetency(HealthWorker $worker, array $attrs, ?UploadedFile $file, ?int $userId): WorkerCompetency
    {
        if ($file) {
            $attrs['document_path'] = SdmkFiles::store($file, "sdmk/{$worker->id}/kompetensi");
        }

        $row = $worker->competencies()->create($attrs);
        SdmkActivityRecorded::dispatch($worker->id, 'riwayat', 'Kompetensi tambahan ditambahkan', $row->nama, $userId);

        return $row;
    }

    public function deleteCompetency(HealthWorker $worker, WorkerCompetency $row, ?int $userId): void
    {
        $path = $row->document_path;
        $row->delete();
        SdmkFiles::delete($path);

        SdmkActivityRecorded::dispatch($worker->id, 'riwayat', 'Kompetensi tambahan dihapus', $row->nama, $userId);
    }
}
