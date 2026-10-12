<?php

namespace App\Http\Controllers\Api\Masterdata\Sdmk;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sdmk\IntegrasiRequest;
use App\Http\Resources\Sdmk\SdmkDetailResource;
use App\Models\HealthWorker;
use App\Services\Sdmk\SdmkIntegrationService;
use App\Services\Sdmk\SdmkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SdmkIntegrationController extends Controller
{
    public function __construct(
        private readonly SdmkIntegrationService $integration,
        private readonly SdmkService $sdmk,
    ) {
    }

    /** PUT /sdmk/{sdmk}/integrasi  (hanya mapping lokal) */
    public function save(IntegrasiRequest $request, HealthWorker $sdmk): JsonResponse
    {
        // Gate::authorize('update', $sdmk);

        $this->integration->saveMapping(
            $sdmk,
            $request->validated('mappingSpesialisasi'),
            $request->validated('mappingTipePraktisi'),
            $request->user()?->id,
        );

        return $this->respond($sdmk);
    }

    /** POST /sdmk/{sdmk}/integrasi/verify  -> 503 bila SATUSEHAT belum dikonfigurasi */
    public function verify(Request $request, HealthWorker $sdmk): JsonResponse
    {
        // Gate::authorize('update', $sdmk);

        $this->integration->verify($sdmk, $request->user()?->id);

        return $this->respond($sdmk);
    }

    private function respond(HealthWorker $worker): JsonResponse
    {
        return (new SdmkDetailResource($this->sdmk->detail($worker->refresh())))->response();
    }
}
