<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Facility extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['is_active' => 'boolean'];

    public function polis(): HasMany
    {
        return $this->hasMany(Poli::class);
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }
}
