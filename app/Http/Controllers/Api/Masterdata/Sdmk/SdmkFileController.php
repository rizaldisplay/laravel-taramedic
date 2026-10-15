<?php

namespace App\Http\Controllers\Api\Masterdata\Sdmk;

use App\Http\Controllers\Controller;
use App\Models\HealthWorker;
use App\Models\WorkerCompetency;
use App\Models\WorkerSip;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Mime\MimeTypes;
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

        $disk = Storage::disk(config('sdmk.disk'));
        abort_unless($path && $disk->exists($path), 404);

        $stream = $disk->readStream($path);
        abort_unless(is_resource($stream), 404);

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $mimeType = MimeTypes::getDefault()->getMimeTypes($extension)[0] ?? 'application/octet-stream';

        return new StreamedResponse(function () use ($stream): void {
            try {
                fpassthru($stream);
            } finally {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $mimeType,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }
}
