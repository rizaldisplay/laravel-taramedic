<?php

namespace App\Enums;

enum QueueTicketStatus: string
{
    case Waiting = 'waiting';
    case Called = 'called';
    case Serving = 'serving';
    case Completed = 'completed';
    case Skip = 'skip';
    case NoShow = 'no_show';

    public function isActive(): bool
    {
        return in_array($this, [self::Waiting, self::Skip, self::Called, self::Serving], true);
    }

    public function isTerminal(): bool
    {
        return ! $this->isActive();
    }

    public function label(): string
    {
        return match ($this) {
            self::Waiting => 'Menunggu',
            self::Skip => 'Terlewati',
            self::Called => 'Dipanggil',
            self::Serving => 'Sedang Dilayani',
            self::Completed => 'Selesai',
            self::NoShow => 'Tidak Hadir',
        };
    }
}
