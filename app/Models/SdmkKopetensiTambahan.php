<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SdmkKopetensiTambahan extends Model
{
    use HasFactory;

    protected $table = 'sdmk_kompetensi_tambahans';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function sdmk(): BelongsTo
    {
        return $this->belongsTo(Sdmk::class);
    }
}