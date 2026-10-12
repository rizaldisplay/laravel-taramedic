<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

class SdmkAlreadyExistsException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('NIK sudah terdaftar sebagai SDMK.');
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'errors' => ['nik' => [$this->getMessage()]],
        ], 422);
    }
}
