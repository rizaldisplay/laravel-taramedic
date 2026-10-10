<?php

namespace App\Support;

final class PhoneNumber
{
    /**
     * Seragamkan nomor HP ke format lokal "08xxxxxxxxxx".
     * Menerima 08xx, 628xx, atau +628xx.
     */
    public static function normalize(?string $number): ?string
    {
        if ($number === null || trim($number) === '') {
            return null;
        }

        $clean = preg_replace('/[^\d+]/', '', $number);

        return preg_replace('/^(\+62|62)/', '0', $clean);
    }
}
