<?php

namespace App\Enums;

enum QueueTicketPriority: string
{
    case Normal = 'normal';
    case Priority = 'priority';
}
