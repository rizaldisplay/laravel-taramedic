<?php

namespace App\Http\Controllers\Api\Masterdata\UnitPoli;

use App\Http\Controllers\Controller;
use App\Http\Requests\UnitPoli\ChangeStatusRequest;
use App\Http\Requests\UnitPoli\ListUnitPoliRequest;
use App\Http\Requests\UnitPoli\UnitPoliRequest;
use App\Http\Resources\UnitPoli\UnitPoliResource;
use App\Enums\Masterdata\UnitPoli\StatusUnit;
use App\Models\Poli;
use App\Services\UnitPoliService;
use App\DTOs\Masterdata\UnitPoliData as MasterdataUnitPoliData;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class UnitPoliController extends Controller
{
    public function __construct(private readonly UnitPoliService $service)
    {
    }

    /** GET /unit-poli?search=&status=&jenis=&page=&perPage= -> { data, meta, ringkasan } */
    public function index(ListUnitPoliRequest $request): JsonResponse
    {
        // Gate::authorize('viewAny', Poli::class);

        $p = $this->service->list(
            $request->validated('search'),
            $request->validated('status'),
            $request->validated('jenis'),
            $request->pageNumber(),
            $request->perPage(),
        );

        return response()->json([
            'data' => UnitPoliResource::collection($p->items()),
            'meta' => ['currentPage' => $p->currentPage(), 'lastPage' => max(1, $p->lastPage()), 'perPage' => $p->perPage(), 'total' => $p->total()],
            'ringkasan' => $this->service->summary(),
        ]);
    }

    /** GET /unit-poli/options -> { fasyankes: Opsi[], jenis: Opsi[] } */
    public function options(): JsonResponse
    {
        // Gate::authorize('viewAny', Poli::class);

        return response()->json($this->service->options());
    }

    /** POST /unit-poli -> UnitPoliItem (201) */
    public function store(UnitPoliRequest $request): JsonResponse
    {
        // Gate::authorize('create', Poli::class);

        $data = $request->toData();
        $poli = $this->service->register(new MasterdataUnitPoliData(...get_object_vars($data)), $request->user()?->id);

        return (new UnitPoliResource($poli))->response()->setStatusCode(201);
    }

    /** PUT /unit-poli/{poli} -> UnitPoliItem */
    public function update(UnitPoliRequest $request, Poli $poli): UnitPoliResource
    {
        // Gate::authorize('update', $poli);

        $data = $request->toData();

        return new UnitPoliResource($this->service->update($poli, new MasterdataUnitPoliData(...get_object_vars($data)), $request->user()?->id));
    }

    /** PUT /unit-poli/{poli}/status  { status } -> UnitPoliItem */
    public function status(ChangeStatusRequest $request, Poli $poli): UnitPoliResource
    {
        // Gate::authorize('update', $poli);

        return new UnitPoliResource($this->service->setStatus($poli, StatusUnit::from($request->status()->value), $request->user()?->id));
    }
}
