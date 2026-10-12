<?php

namespace App\Http\Controllers\Api\Masterdata\Sdmk;

use App\Http\Controllers\Controller;
use App\Models\HealthWorker;
use App\Models\WorkerCompetency;
use App\Models\WorkerSip;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SdmkFileController extends Controller
{
    /**
     * GET /sdmk/files/{type}/{id}  — hanya lewat URL bertanda tangan (middleware "signed", berlaku 10 menit),
     * karena tag <img>/tab baru tidak membawa token Sanctum.
     */
    public function show(string $type, int $id): StreamedResponse
    {
        $path = match ($type) {
            'foto' => HealthWorker::findOrFail($id)->foto_path,
            'sip' => WorkerSip::findOrFail($id)->document_path,
            'kompetensi' => WorkerCompetency::findOrFail($id)->document_path,
            default => abort(404),
        };

        abort_unless($path && Storage::disk(config('sdmk.disk'))->exists($path), 404);

        return Storage::disk(config('sdmk.disk'))->response($path, null, [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }
}
