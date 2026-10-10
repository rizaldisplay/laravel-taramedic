<?php

namespace App\Http\Controllers\Api\Registrasi;

use App\Http\Controllers\Controller;

use App\Http\Requests\StoreVisitRequest;
use App\Http\Resources\VisitResource;
use App\Services\VisitService;
use Illuminate\Http\JsonResponse;

class VisitController extends Controller
{
    public function __construct(private readonly VisitService $visits)
    {
    }

    /** POST /visits -> VisitResult (201) */
    public function store(StoreVisitRequest $request): JsonResponse
    {
        $visit = $this->visits->register($request->toData(), $request->user()?->id);

        return (new VisitResource($visit))->response()->setStatusCode(201);
    }
}
