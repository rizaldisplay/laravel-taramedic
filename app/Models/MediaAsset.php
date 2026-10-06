<?php

namespace App\Models;

use App\Enums\MediaAssetType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'type',
    'name',
    'disk',
    'path',
    'mime_type',
    'size',
    'is_active',
])]
class MediaAsset extends Model
{
    /** @var array<string, mixed> */
    protected $attributes = [
        'disk' => 'public',
        'is_active' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => MediaAssetType::class,
            'size' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function url(): ?string
    {
        if ($this->path === '') {
            return null;
        }

        return Storage::disk($this->disk)->url($this->path);
    }

    /**
     * @param  Builder<MediaAsset>  $query
     * @return Builder<MediaAsset>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
