<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SdmkJadwalSlot extends Model
{
    use HasFactory;

    protected $table = 'sdmk_jadwal_slots';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'berlaku_mulai' => 'date',
        'berlaku_hingga' => 'date',
        // Note: jam_mulai dan jam_selesai biasanya dibiarkan sebagai string (H:i:s) 
        // karena tipe time di database langsung dipetakan dengan baik oleh PDO.
    ];

    public function sdmk(): BelongsTo
    {
        return $this->belongsTo(Sdmk::class);
    }
}