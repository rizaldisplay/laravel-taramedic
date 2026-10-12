<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

/**
 * Berkas SDMK disimpan di disk PRIVAT dengan nama acak (hashName), bukan nama asli
 * dari pengguna. Pengunduhan hanya lewat URL bertanda tangan yang kedaluwarsa.
 */
final class SdmkFiles
{
    public static function store(UploadedFile $file, string $dir): string
    {
        return $file->store($dir, config('sdmk.disk'));
    }

    public static function delete(?string $path): void
    {
        if ($path) {
            Storage::disk(config('sdmk.disk'))->delete($path);
        }
    }

    /** @param 'foto'|'sip'|'kompetensi' $type */
    public static function url(string $type, int $id, ?string $path): ?string
    {
        return $path
            ? URL::temporarySignedRoute('sdmk.files.show', now()->addMinutes(10), ['type' => $type, 'id' => $id])
            : null;
    }
}
