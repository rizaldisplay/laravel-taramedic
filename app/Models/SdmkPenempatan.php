<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SdmkPenempatan extends Model
{
    use HasFactory;

    protected $table = 'sdmk_penempatans';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_berakhir' => 'date',
    ];

    public function sdmk(): BelongsTo
    {
        return $this->belongsTo(Sdmk::class);
    }
}