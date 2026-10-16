<?php

namespace App\Http\Controllers\Api\Masterdata\TarifObat;

use App\Http\Controllers\Controller;
use App\Http\Requests\TarifObat\ChangeTarifStatusRequest;
use App\Http\Requests\TarifObat\ListTarifObatRequest;
use App\Http\Requests\TarifObat\TarifObatRequest;
use App\Http\Resources\RiwayatTarifResource;
use App\Http\Resources\TarifObatResource;
use App\Models\DrugPrice;
use App\Services\TarifObat\TarifObatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class TarifObatController extends Controller
{
    public function __construct(private readonly TarifObatService $service)
    {
    }

    /** GET /farmasi/tarif-obat?search=&penjamin=&status=&page=&perPage= -> { data, meta } */
    public function index(ListTarifObatRequest $request): JsonResponse
    {
        Gate::authorize('viewAny', DrugPrice::class);

        $p = $this->service->list(
            $request->validated('search'),
            $request->validated('penjamin'),
            $request->validated('status'),
            $request->pageNumber(),
            $request->perPage(),
        );

        return response()->json([
            'data' => TarifObatResource::collection($p->items()),
            'meta' => ['currentPage' => $p->currentPage(), 'lastPage' => max(1, $p->lastPage()), 'perPage' => $p->perPage(), 'total' => $p->total()],
        ]);
    }

    /** GET /farmasi/tarif-obat/options -> { obat: [{value,label,satuan}], penjamin: string[] } */
    public function options(): JsonResponse
    {
        Gate::authorize('viewAny', DrugPrice::class);

        return response()->json($this->service->options());
    }

    /** POST /farmasi/tarif-obat -> TarifObatItem (201) */
    public function store(TarifObatRequest $request): JsonResponse
    {
        Gate::authorize('create', DrugPrice::class);

        $tarif = $this->service->register($request->toData(), $request->user()?->id);

        return (new TarifObatResource($tarif))->response()->setStatusCode(201);
    }

    /** PUT /farmasi/tarif-obat/{tarif} -> TarifObatItem */
    public function update(TarifObatRequest $request, DrugPrice $tarif): TarifObatResource
    {
        Gate::authorize('update', $tarif);

        return new TarifObatResource($this->service->update($tarif, $request->toData(), $request->user()?->id));
    }

    /** PUT /farmasi/tarif-obat/{tarif}/status  { status } -> TarifObatItem */
    public function status(ChangeTarifStatusRequest $request, DrugPrice $tarif): TarifObatResource
    {
        Gate::authorize('update', $tarif);

        return new TarifObatResource($this->service->setStatus($tarif, $request->status(), $request->user()?->id));
    }

    /** GET /farmasi/tarif-obat/{tarif}/riwayat -> { data: RiwayatTarifItem[] } (terbaru dulu) */
    public function riwayat(DrugPrice $tarif): JsonResponse
    {
        Gate::authorize('view', $tarif);

        return response()->json(['data' => RiwayatTarifResource::collection($this->service->riwayat($tarif))]);
    }
}
