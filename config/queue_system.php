<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Ticket Number Padding
    |--------------------------------------------------------------------------
    |
    | Number of digits used after the service code prefix (e.g. A001).
    |
    */

    'ticket_number_padding' => (int) env('QUEUE_TICKET_NUMBER_PADDING', 3),

    /*
    |--------------------------------------------------------------------------
    | Default Estimated Service Minutes
    |--------------------------------------------------------------------------
    |
    | Fallback when a service has no estimated_minutes configured.
    |
    */

    'default_estimated_minutes' => (int) env('QUEUE_DEFAULT_ESTIMATED_MINUTES', 5),

    /*
    |--------------------------------------------------------------------------
    | Announcement Template
    |--------------------------------------------------------------------------
    |
    | Placeholders: {ticket_number}, {service_name}, {counter_name}, {counter_code}
    |
    */

    'default_tts_template' => 'Nomor antrian {ticket_number}, silakan menuju loket {counter_name}.',

    /*
    |--------------------------------------------------------------------------
    | Display Board
    |--------------------------------------------------------------------------
    |
    | Number of previous calls shown on the waiting room display.
    |
    */

    'display_recent_limit' => (int) env('QUEUE_DISPLAY_RECENT_LIMIT', 4),

    /*
    |--------------------------------------------------------------------------
    | Recall Deduplication Window (seconds)
    |--------------------------------------------------------------------------
    |
    | Repeated recall requests inside this window reuse the same call so a
    | double click cannot produce two announcements.
    |
    */

    'recall_deduplication_seconds' => (int) env('QUEUE_RECALL_DEDUPLICATION_SECONDS', 2),

    /*
    |--------------------------------------------------------------------------
    | Display TV Video Upload (MB)
    |--------------------------------------------------------------------------
    |
    | Maximum video file size for display TV uploads. The effective limit is
    | the lower value between this setting and PHP upload_max_filesize.
    |
    */

    'display_tv_video_max_mb' => (int) env('QUEUE_DISPLAY_TV_VIDEO_MAX_MB', 120),

    'display_tv_video_volume' => (int) env('QUEUE_DISPLAY_TV_VIDEO_VOLUME', 25),

    'display_tv_video_duck_volume' => (int) env('QUEUE_DISPLAY_TV_VIDEO_DUCK_VOLUME', 4),

    /*
    |--------------------------------------------------------------------------
    | Branding (shown on kiosk, display, and login)
    |--------------------------------------------------------------------------
    */

    'institution_name' => env('QUEUE_INSTITUTION_NAME', 'Klinik Pratama Mitra Sehat'),

    'institution_tagline' => env('QUEUE_INSTITUTION_TAGLINE', 'Melayani dengan Hati · Antri Lebih Mudah'),

    /*
    |--------------------------------------------------------------------------
    | Kiosk UX
    |--------------------------------------------------------------------------
    */

    'kiosk_success_reset_seconds' => (int) env('QUEUE_KIOSK_SUCCESS_RESET_SECONDS', 15),

    'kiosk_idle_reset_seconds' => (int) env('QUEUE_KIOSK_IDLE_RESET_SECONDS', 60),

    /*
    |--------------------------------------------------------------------------
    | Auto No-Show (minutes)
    |--------------------------------------------------------------------------
    |
    | Tickets left in "called" status longer than this are marked no-show by
    | the queue:auto-no-show scheduled command. Set to 0 to disable.
    |
    */

    'called_no_show_after_minutes' => (int) env('QUEUE_CALLED_NO_SHOW_AFTER_MINUTES', 5),

];
