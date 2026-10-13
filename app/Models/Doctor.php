<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// MENGGANTIKAN app/Models/Doctor.php
class Doctor extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['is_active' => 'boolean'];

    public function poli(): BelongsTo
    {
        return $this->belongsTo(Poli::class);
    }

    public function healthWorker(): BelongsTo
    {
        return $this->belongsTo(HealthWorker::class);
    }

    /** Nama tampilan: dari SDMK (lengkap dengan gelar) bila terhubung; kolom `name` hanya cadangan. */
    public function displayName(): string
    {
        return $this->health_worker_id && $this->relationLoaded('healthWorker') && $this->healthWorker
            ? $this->healthWorker->displayName()
            : $this->name;
    }

    /**
     * Dokter yang boleh dipilih saat ini:
     *  - baris aktif, dan
     *  - SDMK berstatus Aktif, izin (STR+SIP) belum Tidak Aktif, serta punya penempatan aktif
     *    di poli yang sama pada tanggal hari ini;
     *  - atau (selama masa migrasi, config doctors.allow_unlinked) belum terhubung ke SDMK.
     */
    public function scopeAvailable(Builder $query): Builder
    {
        $today = now(config('sdmk.timezone', 'Asia/Jakarta'))->toDateString();

        return $query->where('doctors.is_active', true)->where(function (Builder $q) use ($today) {
            $q->whereHas('healthWorker', function (Builder $w) use ($today) {
                $w->where('status', 'Aktif')
                    ->where('license_status', '!=', 'Tidak Aktif')
                    ->whereHas('placements', function (Builder $p) use ($today) {
                        $p->where('status', 'Aktif')
                            ->whereColumn('worker_placements.poli_id', 'doctors.poli_id')
                            ->whereDate('tanggal_mulai', '<=', $today)
                            ->where(fn (Builder $d) => $d->whereNull('tanggal_berakhir')->orWhereDate('tanggal_berakhir', '>=', $today));
                    });
            });

            if (config('doctors.allow_unlinked')) {
                $q->orWhereNull('doctors.health_worker_id');
            }
        });
    }
}
