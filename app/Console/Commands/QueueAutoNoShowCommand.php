<?php

namespace App\Console\Commands;

use App\Enums\QueueTicketStatus;
use App\Models\QueueTicket;
use App\Services\QueueManager;
use App\Services\SettingsService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('queue:auto-no-show')]
#[Description('Tandai antrian called yang melewati batas waktu sebagai tidak hadir')]
class QueueAutoNoShowCommand extends Command
{
    public function handle(QueueManager $queue, SettingsService $settings): int
    {
        $minutes = $settings->getInt(
            'called_no_show_after_minutes',
            (int) config('queue_system.called_no_show_after_minutes', 5),
        );

        if ($minutes <= 0) {
            $this->info('Auto no-show dinonaktifkan (batas waktu <= 0).');

            return self::SUCCESS;
        }

        $cutoff = now()->subMinutes($minutes);

        $tickets = QueueTicket::query()
            ->forToday()
            ->where('status', QueueTicketStatus::Called)
            ->whereNotNull('called_at')
            ->where('called_at', '<=', $cutoff)
            ->orderBy('called_at')
            ->lazyById();

        $processed = 0;
        $candidates = 0;

        foreach ($tickets as $ticket) {
            $candidates++;

            try {
                $queue->markNoShow($ticket);
                $processed++;
            } catch (\Throwable $exception) {
                report($exception);
                $this->error("Gagal no-show #{$ticket->ticket_number}: {$exception->getMessage()}");
            }
        }

        $this->info("Auto no-show selesai: {$processed} dari {$candidates} kandidat (batas {$minutes} menit).");

        return self::SUCCESS;
    }
}
