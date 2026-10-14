<?php

namespace App\Http\Controllers\Api\Masterdata\Lokasi;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lokasi\ChangeStatusRequest;
use App\Http\Requests\Lokasi\ListLokasiRequest;
use App\Http\Requests\Lokasi\LokasiRequest;
use App\Http\Resources\Lokasi\LokasiPelayananResource;
use App\Models\ServiceLocation;
use App\Services\Lokasi\LokasiPelayananService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class LokasiPelayananController extends Controller
{
    public function __construct(private readonly LokasiPelayananService $service)
    {
    }

    /** GET /lokasi-pelayanan?search=&status=&tipe=&page=&perPage= -> { data, meta, ringkasan } */
    public function index(ListLokasiRequest $request): JsonResponse
    {
        // Gate::authorize('viewAny', ServiceLocation::class);

        $p = $this->service->list(
            $request->validated('search'),
            $request->validated('status'),
            $request->validated('tipe'),
            $request->pageNumber(),
            $request->perPage(),
        );

        return response()->json([
            'data' => LokasiPelayananResource::collection($p->items()),
            'meta' => ['currentPage' => $p->currentPage(), 'lastPage' => max(1, $p->lastPage()), 'perPage' => $p->perPage(), 'total' => $p->total()],
            'ringkasan' => $this->service->summary(),
        ]);
    }

    /** GET /lokasi-pelayanan/options -> { fasyankes, induk, tipe } */
    public function options(): JsonResponse
    {
        // Gate::authorize('viewAny', ServiceLocation::class);

        return response()->json($this->service->options());
    }

    /** POST /lokasi-pelayanan -> LokasiItem (201) */
    public function store(LokasiRequest $request): JsonResponse
    {
        // Gate::authorize('create', ServiceLocation::class);

        $lokasi = $this->service->register($request->toData(), $request->user()?->id);

        return (new LokasiPelayananResource($lokasi))->response()->setStatusCode(201);
    }

    /** PUT /lokasi-pelayanan/{lokasi} -> LokasiItem */
    public function update(LokasiRequest $request, ServiceLocation $lokasi): LokasiPelayananResource
    {
        // Gate::authorize('update', $lokasi);

        return new LokasiPelayananResource($this->service->update($lokasi, $request->toData(), $request->user()?->id));
    }

    /** PUT /lokasi-pelayanan/{lokasi}/status  { status } -> LokasiItem */
    public function status(ChangeStatusRequest $request, ServiceLocation $lokasi): LokasiPelayananResource
    {
        // Gate::authorize('update', $lokasi);

        return new LokasiPelayananResource($this->service->setStatus($lokasi, $request->status(), $request->user()?->id));
    }
}
