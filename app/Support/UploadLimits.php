<?php

namespace App\Support;

class UploadLimits
{
    public static function displayTvVideoMaxKilobytes(): int
    {
        $configuredKb = (int) config('queue_system.display_tv_video_max_mb', 120) * 1024;

        $phpKb = min(
            self::iniSizeToKilobytes(ini_get('upload_max_filesize')),
            self::iniSizeToKilobytes(ini_get('post_max_size')),
        );

        if ($phpKb <= 0) {
            return $configuredKb;
        }

        return min($configuredKb, $phpKb);
    }

    public static function displayTvVideoMaxMegabytes(): int
    {
        return (int) floor(self::displayTvVideoMaxKilobytes() / 1024);
    }

    /**
     * @return array<int, string>
     */
    public static function displayTvVideoMimeRules(): array
    {
        return ['mp4', 'webm', 'mov', 'avi', 'mkv', 'm4v'];
    }

    private static function iniSizeToKilobytes(string|false $value): int
    {
        if ($value === false || $value === '') {
            return 0;
        }

        $value = trim($value);
        $unit = strtolower(substr($value, -1));
        $number = (int) $value;

        return match ($unit) {
            'g' => $number * 1024 * 1024,
            'm' => $number * 1024,
            'k' => $number,
            default => (int) floor($number / 1024),
        };
    }
}
