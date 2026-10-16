<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Jejak audit satu tarif; hanya ditambah, tidak pernah diubah (tanpa updated_at). */
class DrugPriceHistory extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected $casts = [
        'perubahan' => 'array',
        'created_at' => 'datetime',
    ];

    public function price(): BelongsTo
    {
        return $this->belongsTo(DrugPrice::class, 'drug_price_id');
    }
}
