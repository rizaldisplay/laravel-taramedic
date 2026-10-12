<?php

namespace App\Services\Sdmk;

use App\Actions\Masterdata\Sdmk\CreateHealthWorkerAction;
use App\Actions\Masterdata\Sdmk\UpdateHealthWorkerIdentityAction;
use App\DTOs\Masterdata\HealthWorkerData;
use App\Enums\StatusIhs;
use App\Enums\StatusSdmk;
use App\Events\SdmkActivityRecorded;
use App\Exceptions\SdmkAlreadyExistsException;
use App\Models\HealthWorker;
use App\Models\WorkerActivity;
use App\Services\RegionService;
use App\Support\SdmkFiles;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

final class SdmkService
{
    public function __construct(
        private readonly CreateHealthWorkerAction $create,
        private readonly UpdateHealthWorkerIdentityAction $updateIdentity,
        private readonly RegionService $regions,
    ) {
    }

    public function list(?string $search, ?string $status, int $page, int $perPage): LengthAwarePaginator
    {
        return HealthWorker::query()
            ->with(['placements' => fn ($q) => $q->where('status', 'Aktif')->with('facility')])
            ->when($search, fn (Builder $q) => $q->search($search))
            ->when($status && StatusSdmk::tryFrom($status), fn (Builder $q) => $q->where('status', $status))
            ->orderBy('nama_lengkap')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /** @return array<string, int> */
    public function stats(): array
    {
        $today = now(config('sdmk.timezone'))->toDateString();
        $total = HealthWorker::count();
        $aktif = HealthWorker::where('status', StatusSdmk::Aktif)->count();

        $kadaluwarsa = HealthWorker::query()
            ->where(function (Builder $q) use ($today) {
                $q->whereDate('str_expires_at', '<', $today)
                    ->orWhere(function (Builder $q) use ($today) {
                        $q->whereHas('sips')
                            ->whereDoesntHave('sips', fn ($s) => $s->where('status', '!=', 'Tidak Aktif')->whereDate('berlaku_hingga', '>=', $today));
                    });
            })->count();

        return [
            'total' => $total,
            'aktif' => $aktif,
            'tidakAktif' => $total - $aktif,
            'izinSegeraBerakhir' => HealthWorker::where('license_status', 'Akan Berakhir')->count(),
            'izinKadaluwarsa' => $kadaluwarsa,
            'belumTersinkron' => HealthWorker::where('ihs_status', '!=', StatusIhs::Terhubung)->count(),
        ];
    }

    public function findByNik(string $nik, ?int $exceptId = null): ?HealthWorker
    {
        return HealthWorker::where('nik', $nik)->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))->first();
    }

    /** Muat semua relasi yang dibutuhkan SdmkDetailResource. */
    public function detail(HealthWorker $worker): HealthWorker
    {
        return $worker->load([
            'competencies',
            'sips.facility', 'sips.poli', 'sips.room',
            'placements.facility', 'placements.poli', 'placements.room',
            'scheduleDays', 'scheduleSlots',
            'apiLogs' => fn ($q) => $q->limit(20),
        ]);
    }

    public function register(HealthWorkerData $data, ?int $userId): HealthWorker
    {
        try {
            return $this->create->execute($data, $this->regionNames($data), $userId);
        } catch (UniqueConstraintViolationException) {
            throw new SdmkAlreadyExistsException();   // pendaftaran serentak dengan NIK sama
        }
    }

    public function updateIdentity(HealthWorker $worker, HealthWorkerData $data, ?int $userId): HealthWorker
    {
        try {
            return $this->updateIdentity->execute($worker, $data, $this->regionNames($data), $userId);
        } catch (UniqueConstraintViolationException) {
            throw new SdmkAlreadyExistsException();
        }
    }

    public function storePhoto(HealthWorker $worker, UploadedFile $file, ?int $userId): void
    {
        $old = $worker->foto_path;
        $worker->update(['foto_path' => SdmkFiles::store($file, "sdmk/{$worker->id}/foto"), 'updated_by' => $userId]);
        SdmkFiles::delete($old);

        SdmkActivityRecorded::dispatch($worker->id, 'riwayat', 'Foto diperbarui', null, $userId);
    }

    /** @param array<string, mixed> $attrs kolom profesi (snake_case) */
    public function saveProfesi(HealthWorker $worker, array $attrs, ?int $userId): void
    {
        $worker->update([...$attrs, 'updated_by' => $userId]);
        SdmkActivityRecorded::dispatch($worker->id, 'riwayat', 'Profesi & kompetensi diubah', $worker->sdmk_code, $userId);
    }

    public function saveNotes(HealthWorker $worker, ?string $notes, ?int $userId): void
    {
        $worker->update(['notes' => $notes, 'updated_by' => $userId]);
        SdmkActivityRecorded::dispatch($worker->id, 'riwayat', 'Catatan diperbarui', null, $userId);
    }

    /** @return Collection<int, WorkerActivity> */
    public function activities(?HealthWorker $worker, string $category): Collection
    {
        return WorkerActivity::with('user')
            ->where('category', $category)
            ->when($worker, fn ($q) => $q->where('health_worker_id', $worker->id))
            ->latest('created_at')->latest('id')
            ->limit(100)
            ->get();
    }

    /** @return array<string, string> */
    private function regionNames(HealthWorkerData $d): array
    {
        return $this->regions->resolveNames($d->provinsi, $d->kabupatenKota, $d->kecamatan, $d->desaKelurahan);
    }
}
