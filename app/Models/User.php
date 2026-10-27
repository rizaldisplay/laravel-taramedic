<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject; // [BARU] Import interface JWTSubject

#[Fillable([
    'username',
    'name',
    'email',
    'phone_number',
    'phone_verified',
    'phone_verified_at',
    'password',
    'sdmk_id',
    'two_factor_enabled'
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements JWTSubject // [BARU] Implementasikan JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        // Mengembalikan primary key dari user (biasanya 'id')
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        // Opsional: Anda bisa memasukkan custom payload seperti role di sini jika mau
        // Contoh: return ['role' => $this->roles->pluck('slug')];
        return [];
    }

    protected function casts(): array
    {
        return [
            'email_verified_at'  => 'datetime',
            'phone_verified_at'  => 'datetime',
            'password'           => 'hashed',
            'phone_verified'     => 'boolean',
            'two_factor_enabled' => 'boolean',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class); // pastikan withTimestamps() dihapus sesuai error sebelumnya jika belum ditambahkan di migration
    }

    public function createdQueueTickets(): HasMany
    {
        return $this->hasMany(QueueTicket::class, 'created_by_user_id');
    }

    public function handledQueueTickets(): HasMany
    {
        return $this->hasMany(QueueTicket::class, 'handled_by_user_id');
    }

    public function queueCalls(): HasMany
    {
        return $this->hasMany(QueueCall::class);
    }
}