<?php

namespace App\Models;

use App\Enums\QueueCallType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'queue_ticket_id',
    'counter_id',
    'user_id',
    'call_type',
    'called_at',
])]

class QueueCall extends Model
{

    protected $fillable = [
        'queue_ticket_id',
        'antrean_id',
        'counter_id',
        'user_id',
        'call_type',
        'called_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'call_type' => QueueCallType::class,
            'called_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<QueueTicket, $this>
     */
    public function queueTicket(): BelongsTo
    {
        return $this->belongsTo(QueueTicket::class);
    }

    /**
     * @return BelongsTo<Counter, $this>
     */
    public function counter(): BelongsTo
    {
        return $this->belongsTo(Counter::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}