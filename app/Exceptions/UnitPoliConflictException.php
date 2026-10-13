<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

/** Pelanggaran aturan Unit & Poli; dibalas 422 dengan format error validasi Laravel. */
class UnitPoliConflictException extends RuntimeException
{
    public function __construct(public readonly string $field, string $message)
    {
        parent::__construct($message);
    }

    public static function inUse(int $activeDoctors): self
    {
        return new self('status', "Tidak dapat dinonaktifkan: masih ada {$activeDoctors} dokter aktif di unit/poli ini. Nonaktifkan atau pindahkan dokternya terlebih dahulu.");
    }

    public function render(): JsonResponse
    {
        return response()->json(['message' => $this->getMessage(), 'errors' => [$this->field => [$this->getMessage()]]], 422);
    }
}
