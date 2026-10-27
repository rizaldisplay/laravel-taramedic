<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefreshToken extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'token',
        'device_id', // TAMBAHKAN BARIS INI
        'ip_address',
        'user_agent',
        'last_used_at',
        'expires_at',
        'is_revoked',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}