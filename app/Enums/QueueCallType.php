<?php

namespace App\Enums;

enum QueueCallType: string
{
    case Initial = 'initial';
    case Waiting = 'waiting';
    case Skip = 'skip';
    case Recall = 'recall';
    case Takeover = 'takeover';
}