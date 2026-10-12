<?php

namespace App\Http\Controllers\Api\Masterdata\Sdmk;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sdmk\CatatanRequest;
use App\Http\Requests\Sdmk\FotoRequest;
use App\Http\Requests\Sdmk\IdentitasRequest;
use App\Http\Requests\Sdmk\ListSdmkRequest;
use App\Http\Requests\Sdmk\ProfesiRequest;
use App\Http\Resources\Sdmk\SdmkDetailResource;
use App\Http\Resources\Sdmk\SdmkHistoryResource;
use App\Http\Resources\Sdmk\SdmkListResource;
use App\Models\HealthWorker;
use App\Services\Sdmk\SdmkOptionsService;
use App\Services\Sdmk\SdmkService;
use App\Support\SdmkMask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class SdmkController extends Controller
{
    public function __construct(private readonly SdmkService $sdmk)
    {
    }

    /** GET /sdmk?search=&status=&page=&perPage= -> { data: SdmkListItem[], meta } */
    public function index(ListSdmkRequest $request): JsonResponse
    {
        // Gate::authorize('viewAny', HealthWorker::class);

        $p = $this->sdmk->list($request->validated('search'), $request->validated('status'), $request->pageNumber(), $request->perPage());

        return response()->json([
            'data' => SdmkListResource::collection($p->items()),
            'meta' => ['currentPage' => $p->currentPage(), 'lastPage' => max(1, $p->lastPage()), 'perPage' => $p->perPage(), 'total' => $p->total()],
        ]);
    }

    /** GET /sdmk/stats -> SdmkStats */
    public function stats(): JsonResponse
    {
        // Gate::authorize('viewAny', HealthWorker::class);

        return response()->json($this->sdmk->stats());
    }

    /** GET /sdmk/options -> SdmkOptions */
    public function options(SdmkOptionsService $options): JsonResponse
    {
        // Gate::authorize('viewAny', HealthWorker::class);

        return response()->json($options->all());
    }

    /** GET /sdmk/check-nik/{nik}?exceptId= -> { terdaftar, nama? }  (throttle ketat di routes) */
    public function checkNik(Request $request, string $nik): JsonResponse
    {
        // Gate::authorize('viewAny', HealthWorker::class);

        $found = $this->sdmk->findByNik($nik, $request->integer('exceptId') ?: null);

        // Hanya nama tersamar yang dikembalikan, bukan data lengkap.
        return response()->json($found
            ? ['terdaftar' => true, 'nama' => SdmkMask::name($found->nama_lengkap)]
            : ['terdaftar' => false]);
    }

    /** GET /sdmk/{sdmk} -> SdmkDetail */
    public function show(Request $request, HealthWorker $sdmk): SdmkDetailResource
    {
        // Gate::authorize('view', $sdmk);

        // Jejak akses data pribadi lengkap; tanpa NIK di log.
        Log::info('Data lengkap SDMK dibuka', ['health_worker_id' => $sdmk->id, 'viewed_by' => $request->user()?->id]);

        return new SdmkDetailResource($this->sdmk->detail($sdmk));
    }

    /** POST /sdmk -> SdmkDetail (201) */
    public function store(IdentitasRequest $request): JsonResponse
    {
        // Gate::authorize('create', HealthWorker::class);

        $worker = $this->sdmk->register($request->toData(), $request->user()?->id);

        return $this->respond($worker, 201);
    }

    /** PUT /sdmk/{sdmk}/identitas */
    public function updateIdentitas(IdentitasRequest $request, HealthWorker $sdmk): JsonResponse
    {
        // Gate::authorize('update', $sdmk);

        return $this->respond($this->sdmk->updateIdentity($sdmk, $request->toData(), $request->user()?->id));
    }

    /** POST /sdmk/{sdmk}/foto  (multipart: foto) */
    public function foto(FotoRequest $request, HealthWorker $sdmk): JsonResponse
    {
        // Gate::authorize('update', $sdmk);

        $this->sdmk->storePhoto($sdmk, $request->file('foto'), $request->user()?->id);

        return $this->respond($sdmk);
    }

    /** PUT /sdmk/{sdmk}/profesi */
    public function profesi(ProfesiRequest $request, HealthWorker $sdmk): JsonResponse
    {
        // Gate::authorize('update', $sdmk);

        $this->sdmk->saveProfesi($sdmk, $request->attrs(), $request->user()?->id);

        return $this->respond($sdmk);
    }

    /** PUT /sdmk/{sdmk}/catatan */
    public function catatan(CatatanRequest $request, HealthWorker $sdmk): JsonResponse
    {
        // Gate::authorize('update', $sdmk);

        $this->sdmk->saveNotes($sdmk, $request->validated('catatan'), $request->user()?->id);

        return $this->respond($sdmk);
    }

    /** GET /sdmk/{sdmk}/history dan /sdmk/{sdmk}/audit -> { data: SdmkHistoryItem[] } */
    public function history(Request $request, HealthWorker $sdmk): JsonResponse
    {
        // Gate::authorize('view', $sdmk);

        return $this->activityResponse($sdmk, 'riwayat');
    }

    public function audit(Request $request, HealthWorker $sdmk): JsonResponse
    {
        // Gate::authorize('view', $sdmk);

        return $this->activityResponse($sdmk, 'audit');
    }

    /** GET /sdmk/history  dan  /sdmk/audit  (seluruh SDMK; untuk tombol di header halaman) */
    public function historyAll(): JsonResponse
    {
        // Gate::authorize('viewAny', HealthWorker::class);

        return $this->activityResponse(null, 'riwayat');
    }

    public function auditAll(): JsonResponse
    {
        // Gate::authorize('viewAny', HealthWorker::class);

        return $this->activityResponse(null, 'audit');
    }

    private function activityResponse(?HealthWorker $worker, string $category): JsonResponse
    {
        return response()->json(['data' => SdmkHistoryResource::collection($this->sdmk->activities($worker, $category))]);
    }

    private function respond(HealthWorker $worker, int $status = 200): JsonResponse
    {
        return (new SdmkDetailResource($this->sdmk->detail($worker->refresh())))->response()->setStatusCode($status);
    }
}
