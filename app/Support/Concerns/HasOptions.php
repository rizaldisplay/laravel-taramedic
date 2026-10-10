<?php

namespace App\Support\Concerns;

/**
 * Untuk backed enum yang punya method label().
 * Hasil: [['value' => 'NORMAL', 'label' => 'Normal'], ...]
 */
trait HasOptions
{
    /** @return array<int, array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(
            fn (self $case) => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }
}
