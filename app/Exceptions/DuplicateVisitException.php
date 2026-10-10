<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

class DuplicateVisitException extends RuntimeException
{
    public static function today(): self
    {
        return new self('Pasien sudah didaftarkan di poli ini hari ini.');
    }

    /** Format error validasi Laravel (422) supaya pesan muncul di kolom Poli pada form. */
    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'errors' => ['poliId' => [$this->getMessage()]],
        ], 422);
    }
}
