<?php

namespace App\Http\Controllers\Api\Registrasi;

use App\Http\Controllers\Controller;

use App\Http\Requests\SearchPatientsRequest;
use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use App\Http\Resources\PatientDetailResource;
use App\Http\Resources\PatientResource;
use App\Http\Resources\PatientSummaryResource;
use App\Models\Patient;
use App\Services\PatientService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
// use Illuminate\Support\Facades\Gate;

class PatientController extends Controller
{
    public function __construct(private readonly PatientService $patients)
    {
    }

    /** GET /patients?query=&page=&limit= -> { data: PatientListItem[], meta } */
    public function index(SearchPatientsRequest $request): JsonResponse
    {
        $paginator = $this->patients->search($request->term(), $request->pageNumber(), $request->perPage());

        return response()->json([
            'data' => PatientSummaryResource::collection($paginator->items()),
            'meta' => [
                'currentPage' => $paginator->currentPage(),
                'perPage' => $paginator->perPage(),
                'totalData' => $paginator->total(),
                'lastPage' => max(1, $paginator->lastPage()),
            ],
        ]);
    }

    /** GET /patients/check-nik/{nik} -> { terdaftar: bool, pasien: Pendaftaran|null } */
    public function checkNik(string $nik): JsonResponse
    {
        // Gate::authorize('viewAny', Patient::class);

        $patient = $this->patients->findByNik($nik);

        return response()->json([
            'terdaftar' => $patient !== null,
            'pasien' => $patient ? new PatientResource($patient) : null,
        ]);
    }

    /** POST /patients -> Pendaftaran (201) */
    public function store(StorePatientRequest $request): JsonResponse
    {
        $patient = $this->patients->register($request->toData(), $request->user()?->id);

        return (new PatientResource($patient))->response()->setStatusCode(201);
    }

    /** GET /patients/{patient} -> PatientDetail */
    public function show(Request $request, Patient $patient): PatientDetailResource
    {
        // Gate::authorize('view', $patient);

        return new PatientDetailResource($this->patients->detail($patient, $request->user()?->id));
    }

    /** GET /patients/{patient}/summary -> PatientListItem */
    public function summary(Patient $patient): PatientSummaryResource
    {
        // Gate::authorize('view', $patient);

        return new PatientSummaryResource($this->patients->summary($patient));
    }

    /** PUT /patients/{patient} -> PatientDetail */
    public function update(UpdatePatientRequest $request, Patient $patient): PatientDetailResource
    {
        $updated = $this->patients->update($patient, $request->toData(), $request->user()?->id);

        return new PatientDetailResource($updated);
    }
}
