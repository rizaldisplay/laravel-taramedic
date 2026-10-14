<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// MENGGANTIKAN WorkerPlacement.php dari paket SDMK (menambah relasi healthWorker).
class WorkerPlacement extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_berakhir' => 'date',
        'is_unit_head' => 'boolean',
    ];

    public function healthWorker(): BelongsTo
    {
        return $this->belongsTo(HealthWorker::class);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function poli(): BelongsTo
    {
        return $this->belongsTo(Poli::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(ServiceLocation::class, 'service_location_id');
    }
}
