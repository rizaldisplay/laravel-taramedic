<?php

namespace App\Enums;

enum QueueTicketSource: string
{
    case Kiosk = 'kiosk';
    case Operator = 'operator';
}
