<?php

namespace App\Enums;

enum QueueCallQueue: string
{
    case Waiting = 'waiting';
    case Skip = 'skip';
}
