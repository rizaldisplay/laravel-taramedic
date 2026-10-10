<?php

namespace App\Support;

class MaskData
{
    /** 3271123456789012 -> 3271••••••••9012 */
    public static function nik(?string $nik): ?string
    {
        if (blank($nik)) {
            return null;
        }

        if (strlen($nik) <= 8) {
            return str_repeat('•', strlen($nik));
        }

        return substr($nik, 0, 4).str_repeat('•', strlen($nik) - 8).substr($nik, -4);
    }

    /** 081512345678 -> 0815•••••••• */
    public static function telepon(?string $nomor): ?string
    {
        if (blank($nomor)) {
            return null;
        }

        return substr($nomor, 0, 4).str_repeat('•', max(strlen($nomor) - 4, 0));
    }
}
