<?php

namespace App\Services;

use App\Actions\Registrasi\Visit\CreateVisit;
use App\DTOs\Registrasi\VisitData;
use App\Enums\Patient\CaraMasuk;
use App\Enums\Patient\JenisLayanan;
use App\Enums\Patient\JenisPembayaran;
use App\Enums\Patient\Penjamin;
use App\Enums\Patient\Prioritas;
use App\Exceptions\DuplicateVisitException;
use App\Models\Doctor;
use App\Models\Poli;
use App\Models\ServiceLocation;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Database\UniqueConstraintViolationException;

// MENGGANTIKAN app/Services/VisitService.php: dokter berasal dari SDMK (Doctor::available()), poli hanya yang untuk antrean.
final class VisitService
{
    public function __construct(private readonly CreateVisit $createVisit)
    {
    }

    /** Semua opsi dropdown pendaftaran kunjungan (kunci camelCase sesuai frontend; bentuk tidak berubah). */
    public function options(?User $user): array
    {
        return [
            'poli' => Poli::query()->where('is_active', true)->where('untuk_antrean', true)->orderBy('name')->get()
                ->map(fn (Poli $p) => ['id' => $p->id, 'nama' => $p->name])->all(),
            'dokter' => Doctor::query()->available()->with('healthWorker')->get()
                ->sortBy(fn (Doctor $d) => $d->displayName())
                ->map(fn (Doctor $d) => ['id' => $d->id, 'nama' => $d->displayName(), 'poliId' => $d->poli_id])
                ->values()->all(),
            'jenisLayanan' => JenisLayanan::values(),
            'caraMasuk' => CaraMasuk::values(),
            'penjamin' => Penjamin::values(),
            'jenisPembayaran' => JenisPembayaran::values(),
            'prioritas' => Prioritas::values(),
            'loket' => collect(config('registration.counters'))
                ->map(fn (string $nama, int $id) => ['id' => $id, 'nama' => $nama])->values()->all(),
            'lokasi' => ServiceLocation::query()->where('is_active', true)->orderBy('name')->get()
                ->map(fn (ServiceLocation $l) => ['id' => $l->id, 'nama' => $l->name])->all(),
            'petugas' => ['id' => $user?->id, 'nama' => $user?->name ?? '-'],
        ];
    }

    public function register(VisitData $data, ?int $registeredBy = null): Visit
    {
        try {
            $visit = $this->createVisit->execute($data, $registeredBy);
        } catch (UniqueConstraintViolationException) {
            throw DuplicateVisitException::today();
        }

        return $visit->load(['poli', 'doctor.healthWorker', 'location']);
    }
}
