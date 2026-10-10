<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sdmk extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'sdmks';

    // Mengizinkan mass-assignment untuk semua kolom kecuali id dan timestamps
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    // Cast tipe data agar otomatis menjadi Carbon instance dan Array
    protected $casts = [
        'tanggal_lahir' => 'date',
        'str_tanggal_terbit' => 'date',
        'str_berlaku_hingga' => 'date',
        'str_tanggal_verifikasi' => 'date',
        'jadwal_jenis_pasien' => 'array', // Otomatis mengubah JSON string menjadi PHP Array
    ];

    /**
     * Relasi One-to-Many ke Surat Izin Praktik (SIP)
     */
    public function sips(): HasMany
    {
        return $this->hasMany(SdmkSip::class);
    }

    /**
     * Relasi One-to-Many ke Penempatan
     */
    public function penempatans(): HasMany
    {
        return $this->hasMany(SdmkPenempatan::class);
    }

    /**
     * Relasi One-to-Many ke Jadwal Praktik
     */
    public function jadwalSlots(): HasMany
    {
        return $this->hasMany(SdmkJadwalSlot::class);
    }

    /**
     * Relasi One-to-Many ke Kompetensi Tambahan / Sertifikat
     */
    public function kompetensiTambahans(): HasMany
    {
        return $this->hasMany(SdmkKompetensiTambahan::class);
    }
}