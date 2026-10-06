<?php

namespace App\Http\Controllers\Api\Queue;

use App\Http\Controllers\Controller;
use App\Actions\Display\GetCounterBoardData;
use App\Events\QueueDisplayUpdated;
use App\Events\QueueUpdated;
use App\Exceptions\Queue\QueueDomainException;
use App\Enums\QueueCallQueue;
use App\DTOs\Queue\CalledTicketData;
use App\Models\Antrean;
use App\Models\Counter;
use App\Models\QueueTicket;
use App\Services\QueueManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CounterController extends Controller
{
    public function __construct(
        protected QueueManager $queueManager
    ) {}

    /**
     * Menyusun payload announcement dari CalledTicketData.
     * Struktur ini dipertahankan persis karena dipakai sebagai kontrak
     * data oleh event broadcast (QueueDisplayUpdated) & listener frontend.
     */
    private function buildAnnouncementData(CalledTicketData $calledTicket, Counter $counter, string $messageTemplate): array
    {
        $ticketModel = $calledTicket->ticket;
        $call = $calledTicket->call;

        $ticketNumber = $ticketModel->ticket_number;
        $callId = $call->id;
        $calledAt = $call->called_at; // sudah Carbon instance (cast 'datetime' di QueueCall)

        return [
            'message'       => sprintf($messageTemplate, $ticketNumber),
            'ticket_number' => $ticketNumber,
            'service_name'  => $ticketModel->service?->name,
            'service_code'  => $ticketModel->service?->code,
            'counter_name'  => $counter->name,
            'counter_code'  => $counter->code,
            'call_id'       => $callId . '-' . time(),
            'called_at'     => $calledAt?->toIso8601String(),
        ];
    }

    public function dashboard(Counter $counter, GetCounterBoardData $getDashboardData): JsonResponse
    {
        $dashboardData = $getDashboardData->handle($counter);
        return response()->json($dashboardData->toArray());
    }

    public function callNext(Request $request, Counter $counter): JsonResponse
    {
        try {
            // Log::channel('stderr')->info('Data Loket: ' . $request->loket);
            Log::channel('stderr')->info('Data Ticket: ' . $request->ticket);

            $calledTicket = $this->queueManager->callNext(
                id: $request->ticket,
                loket: $request->loket,
                counter: $counter,
                operator: $request->user(),
                queue: QueueCallQueue::Waiting
            );

            $announcementData = $this->buildAnnouncementData(
                $calledTicket,
                $counter,
                "Nomor antrian %s, silakan menuju loket registrasi"
            );

            // Catatan: sebelumnya dispatch() di sini TANPA argumen,
            // beda dengan callNextSkip & recall yang mengirim $announcementData.
            // Kalau QueueDisplayUpdated butuh payload untuk update display board,
            // ini juga perlu dikirim — cek isi QueueDisplayUpdated di bawah.
            QueueDisplayUpdated::dispatch($announcementData);
            QueueUpdated::dispatch();

            return response()->json([
                'success' => true,
                'message' => "Nomor antrian {$announcementData['ticket_number']} berhasil dipanggil.",
                'data'    => $announcementData
            ], 200);

        } catch (QueueDomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => class_basename($e),
            ], 400);
        }
    }

    public function callNextSkip(Request $request, Counter $counter): JsonResponse
    {
        try {
            $calledTicket = $this->queueManager->callNext(
                id: $request->selected_id ?? null,
                loket: $request->loket,
                counter: $counter,
                operator: $request->user(),
                queue: QueueCallQueue::Skip
            );

            $announcementData = $this->buildAnnouncementData(
                $calledTicket,
                $counter,
                "Nomor antrian %s, silakan menuju loket registrasi"
            );

            QueueDisplayUpdated::dispatch($announcementData);
            QueueUpdated::dispatch();

            return response()->json([
                'success' => true,
                'message' => "Nomor antrian terlewat {$announcementData['ticket_number']} berhasil dipanggil.",
                'data'    => $announcementData
            ], 200);

        } catch (QueueDomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => class_basename($e),
            ], 400);
        }
    }

    public function recall(Request $request, Counter $counter, QueueTicket $ticket): JsonResponse
    {
        Log::channel('stderr')->info('Data Loket: ' . $request->loket);

        try {
            $recalledTicket = $this->queueManager->recall(
                ticket: $ticket,
                counter: $counter,
                loket: $request->loket,
                operator: $request->user()
            );

            $announcementData = $this->buildAnnouncementData(
                $recalledTicket,
                $counter,
                "Nomor antrean %s, silakan menuju " . $counter->name
            );

            QueueDisplayUpdated::dispatch($announcementData);
            QueueUpdated::dispatch();

            return response()->json([
                'success' => true,
                'message' => "Pemanggilan ulang antrian {$announcementData['ticket_number']} berhasil.",
                'data'    => $announcementData
            ], 200);
        } catch (QueueDomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => class_basename($e),
            ], 400);
        }
    }

    public function startServing(Request $request, Counter $counter, QueueTicket $ticket): JsonResponse
    {
        try {
            $this->queueManager->startServing(
                ticket: $ticket,
                operator: $request->user()
            );

            QueueUpdated::dispatch();

            return response()->json([
                'success' => true,
                'message' => "Status antrean {$ticket->ticket_number} diubah menjadi dilayani.",
                'data'    => [
                    'ticket_number' => $ticket->ticket_number,
                    'status'        => $ticket->status,
                ]
            ], 200);

        } catch (QueueDomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => class_basename($e),
            ], 400);
        }
    }

    public function defer(Counter $counter, QueueTicket $ticket): JsonResponse
    {
        try {
            $this->queueManager->defer($ticket);
            QueueUpdated::dispatch();

            return response()->json([
                'success' => true,
                'message' => "Status antrean {$ticket->ticket_number} diubah menjadi dilewati.",
                'data'    => [
                    'ticket_number' => $ticket->ticket_number,
                    'status'        => $ticket->status,
                ]
            ], 200);
        } catch (QueueDomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => class_basename($e),
            ], 400);
        }
    }

    public function markNoShow(QueueTicket $ticket): RedirectResponse
    {
        try {
            $this->queueManager->markNoShow($ticket);
            return back()->with('warning', "Antrian {$ticket->ticket_number} ditandai tidak hadir.");
        } catch (QueueDomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function complete(Request $request, QueueTicket $ticket): RedirectResponse
    {
        try {
            $this->queueManager->complete(
                ticket: $ticket,
                operator: $request->user()
            );
            return back()->with('success', "Pelayanan antrian {$ticket->ticket_number} selesai.");
        } catch (QueueDomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}