<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'group',
    'key',
    'value',
])]
class Setting extends Model
{
    /** @var array<string, mixed> */
    protected $attributes = [
        'group' => 'general',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }

    /**
     * @param  Builder<Setting>  $query
     * @return Builder<Setting>
     */
    public function scopeInGroup(Builder $query, string $group): Builder
    {
        return $query->where('group', $group);
    }
}
