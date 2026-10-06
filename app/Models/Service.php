<?php

namespace App\Models;

use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code',
    'name',
    'estimated_minutes',
    'sort_order',
    'is_active',
])]
class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'estimated_minutes',
        'sort_order',
        'is_active',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'estimated_minutes' => 5,
        'sort_order' => 0,
        'is_active' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estimated_minutes' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsToMany<Counter, $this>
     */
    public function counters(): BelongsToMany
    {
        return $this->belongsToMany(Counter::class)->withTimestamps();
    }

    /**
     * @return HasMany<QueueTicket, $this>
     */
    public function queueTickets(): HasMany
    {
        return $this->hasMany(QueueTicket::class);
    }

    /**
     * @param  Builder<Service>  $query
     * @return Builder<Service>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<Service>  $query
     * @return Builder<Service>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
