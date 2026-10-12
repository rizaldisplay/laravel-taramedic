<?php

namespace App\Enums;

use App\Support\HasOptions;

/** Nilai = kolom sdmk_references.type. */
enum SdmkReferenceType: string
{
    use HasOptions;

    case JenisSdmk = "jenis_sdmk";
    case Profesi = "profesi";
    case KompetensiUtama = "kompetensi_utama";
    case Spesialisasi = "spesialisasi";
    case SubSpesialisasi = "sub_spesialisasi";
    case TingkatPendidikan = "tingkat_pendidikan";
    case OrganisasiStr = "organisasi_str";
    case JenisSip = "jenis_sip";
    case PeranUnit = "peran_unit";
    case Jabatan = "jabatan";
    case TipeJadwal = "tipe_jadwal";
    case JenisPasien = "jenis_pasien";
    case PolaJadwal = "pola_jadwal";
    case MappingSpesialisasi = "mapping_spesialisasi";
    case TipePraktisi = "tipe_praktisi";
}
