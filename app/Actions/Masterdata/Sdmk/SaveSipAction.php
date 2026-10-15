<?php

namespace App\Actions\Masterdata\Sdmk;

use App\Events\SdmkActivityRecorded;
use App\Exceptions\SdmkRuleException;
use App\Models\HealthWorker;
use App\Models\WorkerSip;
use App\Support\SdmkFiles;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;


final class SaveSipAction
{
    public function __construct(private readonly RecomputeLicenseStatusAction $recompute)
    {
    }

    /**
     * @param array<string, mixed> $attrs kolom worker_sips (sudah snake_case + nama wilayah)
     */
    public function execute(HealthWorker $worker, array $attrs, ?UploadedFile $file, ?WorkerSip $sip, ?int $userId): WorkerSip
    {
        $oldPath = $sip?->document_path;

        if ($file) {
            $attrs['document_path'] = SdmkFiles::store($file, "sdmk/{$worker->id}/sip");
        } elseif (! $sip) {
            throw new SdmkRuleException('dokumen', 'Berkas SIP wajib diunggah.');
        }

        try {
            $sip = $sip
                ? tap($sip)->update($attrs)
                : $worker->sips()->create($attrs);
        } catch (UniqueConstraintViolationException) {
            if ($file) {
                SdmkFiles::delete($attrs['document_path']);
            }
            throw new SdmkRuleException('nomorSip', 'Nomor SIP ini sudah tercatat untuk SDMK tersebut.');
        }

        if ($file) {
            SdmkFiles::delete($oldPath);
        }

        $this->recompute->execute($worker);
        SdmkActivityRecorded::dispatch($worker->id, 'riwayat', 'SIP disimpan', $sip->jenis_sip, $userId);

        return $sip;
    }
}
