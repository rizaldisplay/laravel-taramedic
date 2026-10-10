<?php

namespace App\Models;

use App\Enums\Patient\CaraMasuk;
use App\Enums\Patient\JenisLayanan;
use App\Enums\Patient\JenisPembayaran;
use App\Enums\Patient\Penjamin;
use App\Enums\Patient\Prioritas;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Visit extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'jenis_layanan' => JenisLayanan::class,
        'cara_masuk' => CaraMasuk::class,
        'penjamin' => Penjamin::class,
        'jenis_pembayaran' => JenisPembayaran::class,
        'prioritas' => Prioritas::class,
        'registered_at' => 'datetime',
        'visit_date' => 'date',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function poli(): BelongsTo
    {
        return $this->belongsTo(Poli::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(ServiceLocation::class, 'service_location_id');
    }

    public function registrar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }
}
