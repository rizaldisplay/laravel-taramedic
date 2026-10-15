<?php

namespace App\Http\Controllers\Api\Masterdata\Sdmk;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sdmk\JadwalRequest;
use App\Http\Requests\Sdmk\PenempatanRequest;
use App\Http\Requests\Sdmk\SlotRequest;
use App\Http\Resources\Sdmk\SdmkDetailResource;
use App\Models\HealthWorker;
use App\Models\WorkerPlacement;
use App\Models\WorkerScheduleSlot;
use App\Services\Sdmk\SdmkScheduleService;
use App\Services\Sdmk\SdmkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/** Penempatan, jadwal mingguan, dan slot waktu. Semua endpoint mengembalikan SdmkDetail terbaru. */
class SdmkScheduleController extends Controller
{
    public function __construct(
        private readonly SdmkScheduleService $schedule,
        private readonly SdmkService $sdmk,
    ) {
    }

    public function storePenempatan(PenempatanRequest $request, HealthWorker $sdmk): JsonResponse
    {
        Gate::authorize('update', $sdmk);

        $this->schedule->savePlacement($sdmk, $request->attrs(), null, $request->user()?->id);

        return $this->respond($sdmk, 201);
    }

    public function updatePenempatan(PenempatanRequest $request, HealthWorker $sdmk, WorkerPlacement $penempatan): JsonResponse
    {
        Gate::authorize('update', $sdmk);
        abort_unless((int) $penempatan->health_worker_id === $sdmk->id, 404);

        $this->schedule->savePlacement($sdmk, $request->attrs(), $penempatan, $request->user()?->id);

        return $this->respond($sdmk);
    }

    public function destroyPenempatan(Request $request, HealthWorker $sdmk, WorkerPlacement $penempatan): JsonResponse
    {
        Gate::authorize('update', $sdmk);
        abort_unless((int) $penempatan->health_worker_id === $sdmk->id, 404);

        $this->schedule->deletePlacement($sdmk, $penempatan, $request->user()?->id);

        return $this->respond($sdmk);
    }

    /** PUT /sdmk/{sdmk}/jadwal  { setting, hari[7] } */
    public function saveJadwal(JadwalRequest $request, HealthWorker $sdmk): JsonResponse
    {
        Gate::authorize('update', $sdmk);

        $this->schedule->saveSchedule($sdmk, $request->settingAttrs(), $request->dayRows(), $request->user()?->id);

        return $this->respond($sdmk);
    }

    public function storeSlot(SlotRequest $request, HealthWorker $sdmk): JsonResponse
    {
        Gate::authorize('update', $sdmk);

        $this->schedule->saveSlot($sdmk, $request->attrs(), null, $request->user()?->id);

        return $this->respond($sdmk, 201);
    }

    public function updateSlot(SlotRequest $request, HealthWorker $sdmk, WorkerScheduleSlot $slot): JsonResponse
    {
        Gate::authorize('update', $sdmk);
        abort_unless((int) $slot->health_worker_id === $sdmk->id, 404);

        $this->schedule->saveSlot($sdmk, $request->attrs(), $slot, $request->user()?->id);

        return $this->respond($sdmk);
    }

    public function destroySlot(Request $request, HealthWorker $sdmk, WorkerScheduleSlot $slot): JsonResponse
    {
        Gate::authorize('update', $sdmk);
        abort_unless((int) $slot->health_worker_id === $sdmk->id, 404);

        $this->schedule->deleteSlot($sdmk, $slot, $request->user()?->id);

        return $this->respond($sdmk);
    }

    private function respond(HealthWorker $worker, int $status = 200): JsonResponse
    {
        return (new SdmkDetailResource($this->sdmk->detail($worker->refresh())))->response()->setStatusCode($status);
    }
}
