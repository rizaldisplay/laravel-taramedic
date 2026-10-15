<?php

namespace App\Http\Controllers\Api\Masterdata\ProdukObat;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProdukObat\ChangeProdukStatusRequest;
use App\Http\Requests\ProdukObat\ListProdukObatRequest;
use App\Http\Requests\ProdukObat\ProdukObatRequest;
use App\Http\Resources\ProdukObat\ProdukObatResource;
use App\Models\DrugProduct;
use App\Services\ProdukObat\ProdukObatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class ProdukObatController extends Controller
{
    public function __construct(private readonly ProdukObatService $service)
    {
    }

    /** GET /farmasi/produk-obat?search=&status=&limit= -> { data, meta: { total, limit } } */
    public function index(ListProdukObatRequest $request): JsonResponse
    {
        // Gate::authorize('viewAny', DrugProduct::class);

        $limit = $request->limit();
        $hasil = $this->service->list($request->validated('search'), $request->validated('status'), $limit);

        return response()->json([
            'data' => ProdukObatResource::collection($hasil['items']),
            'meta' => ['total' => $hasil['total'], 'limit' => $limit],
        ]);
    }

    /** POST /farmasi/produk-obat -> ProdukObatItem (201) */
    public function store(ProdukObatRequest $request): JsonResponse
    {
        // Gate::authorize('create', DrugProduct::class);

        $produk = $this->service->register($request->toData(), $request->user()?->id);

        return (new ProdukObatResource($produk))->response()->setStatusCode(201);
    }

    /** PUT /farmasi/produk-obat/{produk} -> ProdukObatItem */
    public function update(ProdukObatRequest $request, DrugProduct $produk): ProdukObatResource
    {
        // Gate::authorize('update', $produk);

        return new ProdukObatResource($this->service->update($produk, $request->toData(), $request->user()?->id));
    }

    /** PUT /farmasi/produk-obat/{produk}/status  { status } -> ProdukObatItem */
    public function status(ChangeProdukStatusRequest $request, DrugProduct $produk): ProdukObatResource
    {
        // Gate::authorize('update', $produk);

        return new ProdukObatResource($this->service->setStatus($produk, $request->status(), $request->user()?->id));
    }
}
