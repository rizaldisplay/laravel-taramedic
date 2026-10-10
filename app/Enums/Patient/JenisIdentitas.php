<?php

namespace App\Enums\Patient;

enum JenisIdentitas: string
{
    case Nik = 'NIK';
    case TanpaNik = 'TANPA_NIK';
    case Wna = 'WNA';

    /** Pasien tanpa NIK tidak mengisi nomor identitas apa pun. */
    public function memerlukanNomor(): bool
    {
        return $this !== self::TanpaNik;
    }

    public function labelNomor(): string
    {
        return match ($this) {
            self::Wna => 'Nomor paspor',
            default => 'NIK',
        };
    }
}
