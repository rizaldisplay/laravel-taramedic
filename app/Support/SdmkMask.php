<?php

namespace App\Support;

final class SdmkMask
{
    /** "Budi Santoso" -> "B*** S******" : cukup untuk konfirmasi petugas tanpa membocorkan nama lengkap. */
    public static function name(string $name): string
    {
        return collect(preg_split('/\s+/', trim($name)))->filter()
            ->map(fn ($w) => mb_substr($w, 0, 1) . str_repeat('*', max(0, mb_strlen($w) - 1)))
            ->implode(' ');
    }
}
