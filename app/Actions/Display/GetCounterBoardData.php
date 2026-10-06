<?php

namespace App\Actions\Display;

use App\DTOs\Display\DisplayCounterData;
use App\Enums\QueueTicketStatus;
use App\Models\Counter;
use App\Models\QueueTicket;
use App\Models\Service;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class GetCounterBoardData
{
    public function handle(Counter $counter): DisplayCounterData
    {
        $serviceIds = $counter->services()->pluck('services.id');

        // Mengambil antrean yang saat ini sedang dipanggil / dilayani
        $currentTicket = QueueTicket::query()
            ->forToday()
            ->whereIn('status', [QueueTicketStatus::Called, QueueTicketStatus::Serving])
            ->first();

        // Mengambil antrean yang masih menunggu (belum di-skip)
        $waitingTickets = QueueTicket::query()
            ->forToday()
            ->waiting()
            ->where('skip_count', 0)
            ->orderedForCall()
            ->get();

        $skipTickets = QueueTicket::query()
            ->forToday()
            ->waiting('Skip')
            ->where('skip_count', '>', 0)
            ->orderedForCall()
            ->get();

        // Mengambil antrean yang sudah di-skip
        $skippedTickets = QueueTicket::query()
            ->forToday()
            ->waiting()
            ->where('skip_count', '>', 0)
            ->orderedForCall()
            ->get();

        return new DisplayCounterData(
            menunggu: $waitingTickets->count(),
            terlewati: $skippedTickets->count(),
            antreanSaatIni: $currentTicket ? $this->formatTicket($currentTicket, $serviceIds) : null,
            daftarAntrean: $waitingTickets->map(fn ($ticket) => $this->formatTicket($ticket, $serviceIds))->toArray(),
            daftarTerlewati: $skipTickets->map(fn ($ticket) => $this->formatTicket($ticket, $serviceIds))->toArray(),
        );
    }

    /**
     * Mengubah format model QueueTicket menjadi array frontend.
     */
    private function formatTicket(QueueTicket $ticket): array
    {
        return [
            'id' => $ticket->id,
            'nomorAntrean' => $ticket->ticket_number, // Dari 'ticket_number'
            'nomorLoket' =>  $ticket->antrean_id, // Dari 'ticket_number'
            'statusAntrean' => $ticket->status->label(), // Dari method label() pada Enum QueueTicketStatus
            'waktuAmbil' => $ticket->issued_at?->format('H:i') ?? '-', 
            'waktuPanggil' => $ticket->called_at?->format('H:i:s'), 
            'estimasiTunggu' => $this->calculateWaitTime($ticket),
            // Default false, sesuaikan jika ada relasi ke tabel Pasien
            'isPasienBaru' => false, 
            'waitTimeColor' => 'text-green-500', // Bisa dibuat dinamis nantinya
            // Logika: Jika status masih waiting pemanggilan = 0, jika sudah dipanggil = 1 + recall_count
            'pemanggilanKe' => $ticket->status === QueueTicketStatus::Waiting ? 0 : ($ticket->recall_count + 1),
        ];
    }

    /**
     * Kalkulasi sederhana untuk estimasi waktu tunggu pasien
     */
    private function calculateWaitTime(QueueTicket $ticket): ?string
    {
        if ($ticket->status !== QueueTicketStatus::Waiting || !$ticket->issued_at) {
            return null; // Tidak ada estimasi jika sudah dipanggil
        }

        // Contoh: Hitung selisih waktu dari saat tiket dicetak hingga sekarang
        $minutes = (int) $ticket->issued_at->diffInMinutes(now());

        if ($minutes < 1) {
            return '< 1 mnt';
        }

        return "{$minutes} mnt";
    }
}