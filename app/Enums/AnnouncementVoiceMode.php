<?php

namespace App\Enums;

enum AnnouncementVoiceMode: string
{
    case OfflineTts = 'offline_tts';
    case OnlineTts = 'online_tts';
}
