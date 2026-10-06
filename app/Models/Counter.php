<?php

namespace App\Models;

use Database\Factories\CounterFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code',
    'name',
    'sort_order',
    'is_active',
    'claimed_by_user_id',
    'claimed_at',
])]

class Counter extends Model
{
    /** @use HasFactory<CounterFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'sort_order',
        'is_active',
        'claimed_by_user_id',
        'claimed_at',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'sort_order' => 0,
        'is_active' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'claimed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsToMany<Service, $this>
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class)->withTimestamps();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function claimedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'claimed_by_user_id');
    }

    /**
     * @return HasMany<QueueTicket, $this>
     */
    public function queueTickets(): HasMany
    {
        return $this->hasMany(QueueTicket::class);
    }

    /**
     * @return HasMany<QueueCall, $this>
     */
    public function queueCalls(): HasMany
    {
        return $this->hasMany(QueueCall::class);
    }

    public function isClaimed(): bool
    {
        return $this->claimed_by_user_id !== null;
    }

    public function isClaimedBy(?User $user): bool
    {
        return $user !== null && $this->claimed_by_user_id === $user->id;
    }

    /**
     * @param  Builder<Counter>  $query
     * @return Builder<Counter>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<Counter>  $query
     * @return Builder<Counter>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * @param  Builder<Counter>  $query
     * @return Builder<Counter>
     */
    public function scopeUnclaimed(Builder $query): Builder
    {
        return $query->whereNull('claimed_by_user_id');
    }
}
