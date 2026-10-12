<?php

namespace App\Http\Controllers\Api\Masterdata\Sdmk;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sdmk\KompetensiRequest;
use App\Http\Requests\Sdmk\SipRequest;
use App\Http\Requests\Sdmk\StrRequest;
use App\Http\Resources\Sdmk\SdmkDetailResource;
use App\Models\HealthWorker;
use App\Models\WorkerCompetency;
use App\Models\WorkerSip;
use App\Services\Sdmk\SdmkLicenseService;
use App\Services\Sdmk\SdmkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/** STR, SIP, dan kompetensi tambahan. Semua endpoint mengembalikan SdmkDetail terbaru. */
class SdmkLicenseController extends Controller
{
    public function __construct(
        private readonly SdmkLicenseService $licenses,
        private readonly SdmkService $sdmk,
    ) {
    }

    /** PUT /sdmk/{sdmk}/str */
    public function saveStr(StrRequest $request, HealthWorker $sdmk): JsonResponse
    {
        // Gate::authorize('update', $sdmk);

        $this->licenses->saveStr($sdmk, $request->attrs(), $request->validated('sertifikatLain'), $request->user()?->id);

        return $this->respond($sdmk);
    }

    /** POST /sdmk/{sdmk}/str/verify */
    public function verifyStr(Request $request, HealthWorker $sdmk): JsonResponse
    {
        // Gate::authorize('update', $sdmk);

        $this->licenses->verifyStr($sdmk, $request->user()?->id);

        return $this->respond($sdmk);
    }

    /** POST /sdmk/{sdmk}/sip  (multipart; berkas: dokumen) */
    public function storeSip(SipRequest $request, HealthWorker $sdmk): JsonResponse
    {
        // Gate::authorize('update', $sdmk);

        $this->licenses->saveSip($sdmk, $request->attrs(), $request->file('dokumen'), null, $request->user()?->id);

        return $this->respond($sdmk, 201);
    }

    /** POST /sdmk/{sdmk}/sip/{sip} dengan _method=PUT (multipart tidak didukung PUT murni di PHP) */
    public function updateSip(SipRequest $request, HealthWorker $sdmk, WorkerSip $sip): JsonResponse
    {
        // Gate::authorize('update', $sdmk);
        $this->owned($sdmk, $sip);

        $this->licenses->saveSip($sdmk, $request->attrs(), $request->file('dokumen'), $sip, $request->user()?->id);

        return $this->respond($sdmk);
    }

    /** DELETE /sdmk/{sdmk}/sip/{sip} */
    public function destroySip(Request $request, HealthWorker $sdmk, WorkerSip $sip): JsonResponse
    {
        // Gate::authorize('update', $sdmk);
        $this->owned($sdmk, $sip);

        $this->licenses->deleteSip($sdmk, $sip, $request->user()?->id);

        return $this->respond($sdmk);
    }

    /** POST /sdmk/{sdmk}/kompetensi  (multipart; berkas opsional: dokumen) */
    public function storeKompetensi(KompetensiRequest $request, HealthWorker $sdmk): JsonResponse
    {
        // Gate::authorize('update', $sdmk);

        $this->licenses->addCompetency($sdmk, $request->attrs(), $request->file('dokumen'), $request->user()?->id);

        return $this->respond($sdmk, 201);
    }

    /** DELETE /sdmk/{sdmk}/kompetensi/{kompetensi} */
    public function destroyKompetensi(Request $request, HealthWorker $sdmk, WorkerCompetency $kompetensi): JsonResponse
    {
        // Gate::authorize('update', $sdmk);
        $this->owned($sdmk, $kompetensi);

        $this->licenses->deleteCompetency($sdmk, $kompetensi, $request->user()?->id);

        return $this->respond($sdmk);
    }

    /** Cegah akses silang: baris anak harus milik SDMK pada URL. */
    private function owned(HealthWorker $worker, WorkerSip|WorkerCompetency $row): void
    {
        abort_unless((int) $row->health_worker_id === $worker->id, 404);
    }

    private function respond(HealthWorker $worker, int $status = 200): JsonResponse
    {
        return (new SdmkDetailResource($this->sdmk->detail($worker->refresh())))->response()->setStatusCode($status);
    }
}
