<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SdmkSip extends Model
{
    use HasFactory;

    protected $table = 'sdmk_sips';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'tanggal_terbit' => 'date',
        'berlaku_hingga' => 'date',
    ];

    /**
     * Relasi Balik (Inverse) ke SDMK Utama
     */
    public function sdmk(): BelongsTo
    {
        return $this->belongsTo(Sdmk::class);
    }
}