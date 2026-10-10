<?php

namespace App\Exceptions;

use App\Enums\Patient\JenisIdentitas;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class PatientAlreadyExistsException extends RuntimeException
{
    public static function forIdentity(JenisIdentitas $jenis): self
    {
        return new self($jenis->labelNomor() . ' sudah terdaftar sebagai pasien.');
    }

    /**
     * Balas dengan format error validasi Laravel (422) supaya pesan
     * muncul di field "nik" pada form frontend.
     */
    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'errors' => ['nik' => [$this->getMessage()]],
        ], 422);
    }
}
