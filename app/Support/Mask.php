<?php

namespace App\Support;

final class Mask
{
    /** NIK / paspor: 4 karakter awal dan 4 akhir terlihat. "3271123456789012" -> "3271••••••••9012" */
    public static function identity(?string $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        $length = mb_strlen($value);
        if ($length <= 8) {
            return str_repeat('•', $length);
        }

        return mb_substr($value, 0, 4) . str_repeat('•', $length - 8) . mb_substr($value, -4);
    }

    /** Nomor HP: 4 digit awal terlihat. "081512345678" -> "0815••••••••" */
    public static function phone(?string $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        $length = mb_strlen($value);
        if ($length <= 4) {
            return str_repeat('•', $length);
        }

        return mb_substr($value, 0, 4) . str_repeat('•', $length - 4);
    }
}
