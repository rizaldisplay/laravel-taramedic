<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

class IntegrationNotConfiguredException extends RuntimeException
{
    public function __construct(string $message = 'Integrasi SATUSEHAT belum dikonfigurasi. Hubungi administrator.')
    {
        parent::__construct($message);
    }

    public function render(): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], 503);
    }
}
