<?php

namespace App\Support;

/** Untuk backed enum: daftar nilai string (dipakai sebagai opsi dropdown). */
trait HasOptions
{
    /** @return list<string> */
    public static function values(): array
    {
        /** @var class-string<\BackedEnum> $enumClass */
        $enumClass = self::class;

        return array_map(
            static fn (\BackedEnum $case): string => $case->value,
            $enumClass::cases()
        );
    }
}
