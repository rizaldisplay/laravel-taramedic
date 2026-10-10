<?php

namespace App\Support;

use Carbon\CarbonInterface;

class Usia
{
    /** "8 th", "5 bln", atau "12 hr" */
    public static function format(?CarbonInterface $lahir): string
    {
        if (! $lahir) {
            return '-';
        }

        $sekarang = now();

        if (($tahun = $lahir->diffInYears($sekarang)) >= 1) {
            return (int) $tahun.' th';
        }

        if (($bulan = $lahir->diffInMonths($sekarang)) >= 1) {
            return (int) $bulan.' bln';
        }

        return (int) $lahir->diffInDays($sekarang).' hr';
    }
}
