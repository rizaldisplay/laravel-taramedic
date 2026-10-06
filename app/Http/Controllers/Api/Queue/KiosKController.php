<?php
namespace App\Http\Controllers\Api\Queue;

use App\Exceptions\Queue\ServiceNotAvailableException;
use App\Events\QueueUpdated;
use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Services\QueueManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class KiosKController extends Controller
{
    public function __construct(
        private QueueManager $queue
    ) {}

    /**
     * Memproses pengambilan nomor antrean dari mesin Kiosk (Frontend React)
     */
    public function store(Request $request): JsonResponse
    {
        // 1. Validasi request dari Frontend React
        $validated = $request->validate([
            'jenisPenjamin' => 'required|string|in:BPJS,Umum,Asuransi Lain',
            'statusPasien'  => 'required|string|in:Baru,Lama',
            'isPasienBaru'  => 'required|boolean',
        ]);

        // 2. Proteksi Rate Limiting (mencegah spam klik)
        $rateLimitKey = 'kiosk-issue:' . $request->ip();

        if (RateLimiter::tooManyAttempts($rateLimitKey, 10)) {

            return response()->json([
                'success' => false,
                'message' => 'Terlalu banyak permintaan. Coba lagi sebentar.'
            ], 429);
        }

        RateLimiter::hit($rateLimitKey, 60);

        try {
            // 3. Mapping 'Jenis Penjamin' ke 'Service'
            // Anda bisa menyesuaikan logic ini. Contoh: BPJS kode 'A', UMUM kode 'B'.
            $serviceCode = $validated['statusPasien'] === 'Baru' ? 'A' : 'B';
            // Ambil layanan yang aktif berdasarkan kode[cite: 2]
            $service = Service::query()->active()->where('code', $serviceCode)->firstOrFail();

            // 4. Generate tiket via QueueManager
            $result = $this->queue->issueFromKiosk($service);
            $ticketNumber = $result->ticket->ticket_number;

            // 5. Susun respons (sama persis dengan format Livewire sebelumnya)
            $issuedTicket = [
                'ticket_number' => $ticketNumber,
                'ticket_prefix' => $result->ticket->service->code,
                'ticket_sequence' => preg_replace('/\D+/', '', $ticketNumber) ?: $ticketNumber,
                'service_name' => $result->ticket->service->name,
                'service_code' => $result->ticket->service->code,
                'estimated_wait_minutes' => $result->estimatedWaitMinutes,
                'waiting_count' => $result->waitingCount,
                'issued_at' => $result->ticket->issued_at?->format('H:i') ?? now()->format('H:i'),
                'queue_date' => $result->ticket->queue_date?->locale('id')->translatedFormat('d M Y') ?? now()->locale('id')->translatedFormat('d M Y'),
            ];

            QueueUpdated::dispatch();

            return response()->json([
                'success' => true,
                'data'    => $issuedTicket
            ], 201);

        } catch (ServiceNotAvailableException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage()
            ], 400); // Bad Request[cite: 1]
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ], 500); // Internal Server Error
        }
    }
}
