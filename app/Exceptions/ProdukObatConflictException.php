<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

/** Pelanggaran aturan Produk Obat; dibalas 422 dengan format error validasi Laravel. */
class ProdukObatConflictException extends RuntimeException
{
    public function __construct(public readonly string $field, string $message)
    {
        parent::__construct($message);
    }

    public function render(): JsonResponse
    {
        return response()->json(['message' => $this->getMessage(), 'errors' => [$this->field => [$this->getMessage()]]], 422);
    }
}
