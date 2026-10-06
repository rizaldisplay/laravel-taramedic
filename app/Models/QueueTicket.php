<?php

namespace App\Models;

use App\Enums\QueueTicketPriority;
use App\Enums\QueueTicketSource;
use App\Enums\QueueTicketStatus;
use Database\Factories\QueueTicketFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[Fillable([
    'service_id',
    'counter_id',
    'ticket_number',
    'sequence',
    'queue_date',
    'status',
    'source',
    'priority',
    'created_by_user_id',
    'handled_by_user_id',
    'issued_at',
    'called_at',
    'serving_at',
    'completed_at',
    'recall_count',
    'skip_count',
])]

class QueueTicket extends Model
{
    /** @use HasFactory<QueueTicketFactory> */
    use HasFactory;

    protected $fillable = [
    'service_id',
    'antrean_id',
    'counter_id',
    'ticket_number',
    'sequence',
    'queue_date',
    'status',
    'source',
    'priority',
    'created_by_user_id',
    'handled_by_user_id',
    'issued_at',
    'called_at',
    'serving_at',
    'completed_at',
    'recall_count',
    'skip_count',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => QueueTicketStatus::Waiting,
        'priority' => QueueTicketPriority::Normal,
        'recall_count' => 0,
        'skip_count' => 0,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'queue_date' => 'date',
            'status' => QueueTicketStatus::class,
            'source' => QueueTicketSource::class,
            'priority' => QueueTicketPriority::class,
            'issued_at' => 'datetime',
            'called_at' => 'datetime',
            'serving_at' => 'datetime',
            'completed_at' => 'datetime',
            'recall_count' => 'integer',
            'skip_count' => 'integer',
        ];
    }

    /**
     * @return Attribute<Carbon, Carbon|string>
     */
    protected function queueDate(): Attribute
    {
        return Attribute::make(
            set: fn (Carbon|string $value): string => Carbon::parse($value)->toDateString(),
        );
    }

    /**
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
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
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by_user_id');
    }

    /**
     * @return HasMany<QueueCall, $this>
     */
    public function calls(): HasMany
    {
        return $this->hasMany(QueueCall::class);
    }

    /**
     * @param  Builder<QueueTicket>  $query
     * @return Builder<QueueTicket>
     */
    public function scopeWaiting(Builder $query): Builder
    {
        return $query->where('status', QueueTicketStatus::Waiting);
    }

    /**
     * @param  Builder<QueueTicket>  $query
     * @return Builder<QueueTicket>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [
            QueueTicketStatus::Waiting,
            QueueTicketStatus::Called,
            QueueTicketStatus::Serving,
        ]);
    }

    /**
     * @param  Builder<QueueTicket>  $query
     * @return Builder<QueueTicket>
     */
    public function scopeForDate(Builder $query, Carbon|string $date): Builder
    {
        return $query->where('queue_date', Carbon::parse($date)->toDateString());
    }

    /**
     * @param  Builder<QueueTicket>  $query
     * @return Builder<QueueTicket>
     */
    public function scopeForToday(Builder $query): Builder
    {
        return $query->forDate(today());
    }

    /**
     * @param  Builder<QueueTicket>  $query
     * @return Builder<QueueTicket>
     */
    public function scopeOrderedForCall(Builder $query): Builder
    {
        return $query
            ->orderByRaw("CASE priority WHEN 'priority' THEN 0 ELSE 1 END")
            ->orderBy('skip_count')
            ->orderBy('sequence');
    }
}
