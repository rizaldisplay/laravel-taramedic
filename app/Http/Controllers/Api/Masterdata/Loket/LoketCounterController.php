<?php

namespace App\Http\Controllers\Api\Masterdata\Loket;

use App\Http\Controllers\Controller;
use App\Http\Requests\Loket\ChangeLoketStatusRequest;
use App\Http\Requests\Loket\ListLoketRequest;
use App\Http\Requests\Loket\LoketRequest;
use App\Http\Resources\Loket\LoketResource;
use App\Models\Counter;
use App\Services\Loket\LoketCounterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class LoketCounterController extends Controller
{
    public function __construct(private readonly LoketCounterService $service)
    {
    }

    /** GET /loket-counter?search=&status=&tipe=&page=&perPage= -> { data, meta, ringkasan } */
    public function index(ListLoketRequest $request): JsonResponse
    {
        // Gate::authorize('viewAny', Counter::class);

        $p = $this->service->list(
            $request->validated('search'),
            $request->validated('status'),
            $request->validated('tipe'),
            $request->pageNumber(),
            $request->perPage(),
        );

        return response()->json([
            'data' => LoketResource::collection($p->items()),
            'meta' => ['currentPage' => $p->currentPage(), 'lastPage' => max(1, $p->lastPage()), 'perPage' => $p->perPage(), 'total' => $p->total()],
            'ringkasan' => $this->service->summary(),
        ]);
    }

    /** GET /loket-counter/options -> { tipe } */
    public function options(): JsonResponse
    {
        // Gate::authorize('viewAny', Counter::class);

        return response()->json($this->service->options());
    }

    /** POST /loket-counter -> LoketItem (201) */
    public function store(LoketRequest $request): JsonResponse
    {
        // Gate::authorize('create', Counter::class);

        $loket = $this->service->register($request->toData(), $request->user()?->id);

        return (new LoketResource($loket))->response()->setStatusCode(201);
    }

    /** PUT /loket-counter/{loket} -> LoketItem */
    public function update(LoketRequest $request, Counter $loket): LoketResource
    {
        // Gate::authorize('update', $loket);

        return new LoketResource($this->service->update($loket, $request->toData(), $request->user()?->id));
    }

    /** PUT /loket-counter/{loket}/status  { status } -> LoketItem */
    public function status(ChangeLoketStatusRequest $request, Counter $loket): LoketResource
    {
        // Gate::authorize('update', $loket);

        return new LoketResource($this->service->setStatus($loket, $request->status(), $request->user()?->id));
    }
}
