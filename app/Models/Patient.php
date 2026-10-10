<?php

namespace App\Models;

use App\Enums\Patient\JenisIdentitas;
use App\Enums\Patient\JenisKelamin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Patient extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'jenis_identitas' => JenisIdentitas::class,
        'jenis_kelamin' => JenisKelamin::class,
        'tanggal_lahir' => 'date',
        'is_active' => 'boolean',
        'satusehat_synced_at' => 'datetime',
        'nik_verified_at' => 'datetime',
    ];

    // province_code ... village_code berisi kode Wilayah.id; *_name adalah snapshot namanya.

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    public function latestVisit(): HasOne
    {
        return $this->hasOne(Visit::class)->latestOfMany('registered_at');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
