<?php

namespace App\Models;

use App\Enums\Patient\JenisKelamin;
use App\Enums\StatusIhs;
use App\Enums\StatusIzin;
use App\Enums\StatusSdmk;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HealthWorker extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    // Berkas dan nomor identitas tidak ikut ter-serialisasi tanpa sengaja.
    protected $hidden = ['nik', 'foto_path'];

    protected $casts = [
        'tanggal_lahir' => 'date',
        'jenis_kelamin' => JenisKelamin::class,
        'status' => StatusSdmk::class,
        'license_status' => StatusIzin::class,
        'ihs_status' => StatusIhs::class,
        'str_issued_at' => 'date',
        'str_expires_at' => 'date',
        'str_verified_at' => 'datetime',
        'ihs_synced_at' => 'datetime',
        'schedule_patient_types' => 'array',
    ];

    public function competencies(): HasMany
    {
        return $this->hasMany(WorkerCompetency::class)->orderBy('id');
    }

    public function sips(): HasMany
    {
        return $this->hasMany(WorkerSip::class)->orderByDesc('berlaku_hingga');
    }

    public function placements(): HasMany
    {
        return $this->hasMany(WorkerPlacement::class)->orderByDesc('tanggal_mulai');
    }

    public function scheduleDays(): HasMany
    {
        return $this->hasMany(WorkerScheduleDay::class);
    }

    public function scheduleSlots(): HasMany
    {
        return $this->hasMany(WorkerScheduleSlot::class)->orderBy('id');
    }

    public function apiLogs(): HasMany
    {
        return $this->hasMany(WorkerApiLog::class)->latest('created_at')->latest('id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(WorkerActivity::class);
    }

    public function strVerifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'str_verified_by');
    }

    /** Nama tampilan: gelar depan + nama + gelar belakang. */
    public function displayName(): string
    {
        $name = trim(($this->gelar_depan ? $this->gelar_depan . ' ' : '') . $this->nama_lengkap);

        return $this->gelar_belakang ? $name . ', ' . $this->gelar_belakang : $name;
    }

    /** Pencarian: nama, kode SDMK, nomor STR. NIK sengaja tidak dicari dengan LIKE. */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $like = '%' . addcslashes($term, '%_\\') . '%';

        return $query->where(function (Builder $q) use ($like, $term) {
            $q->where('nama_lengkap', 'like', $like)
                ->orWhere('sdmk_code', 'like', $like)
                ->orWhere('str_number', 'like', $like);

            if (preg_match('/^\d{16}$/', $term)) {
                $q->orWhere('nik', $term);
            }
        });
    }
}
