<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkerSip extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['document_path'];

    protected $casts = [
        'tanggal_terbit' => 'date',
        'berlaku_hingga' => 'date',
        'tanggal_sk' => 'date',
    ];

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function poli(): BelongsTo
    {
        return $this->belongsTo(Poli::class);
    }

    /** Lokasi pelayanan bertipe Ruang (tabel service_locations; sebelumnya tabel rooms). */
    public function location(): BelongsTo
    {
        return $this->belongsTo(ServiceLocation::class, 'service_location_id');
    }

    /** SIP berlaku efektif: tanggal belum lewat DAN petugas tidak menandainya Tidak Aktif. */
    public function isEffective(): bool
    {
        return $this->status !== 'Tidak Aktif' && $this->berlaku_hingga->endOfDay()->isFuture();
    }
}
