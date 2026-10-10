<?php

namespace App\Support;

use Carbon\CarbonInterface;

final class Age
{
    /** "8 Tahun", "5 Bulan", atau "12 Hari". */
    public static function format(CarbonInterface $birthDate): string
    {
        $diff = $birthDate->diff(now(), true);

        if ($diff->y >= 1) {
            return "{$diff->y} Tahun";
        }
        if ($diff->m >= 1) {
            return "{$diff->m} Bulan";
        }

        return "{$diff->d} Hari";
    }
}
