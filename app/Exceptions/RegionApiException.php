<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

class RegionApiException extends RuntimeException
{
    public static function unavailable(?\Throwable $previous = null): self
    {
        return new self('Layanan data wilayah sedang tidak tersedia. Coba lagi beberapa saat.', 0, $previous);
    }

    public function render(): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], 503);
    }
}
