<?php

namespace App\Http\Controllers\Api\Registrasi;

use App\Http\Controllers\Controller;

use App\Models\Visit;
use App\Services\VisitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RegistrationOptionsController extends Controller
{
    /** GET /registration/options -> VisitOptions */
    public function __invoke(Request $request, VisitService $visits): JsonResponse
    {
        // Gate::authorize('create', Visit::class);

        return response()->json($visits->options($request->user()));
    }
}
