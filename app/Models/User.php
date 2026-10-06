<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /** @var array<string, mixed> */
    protected $attributes = [
        'role' => 'operator',
    ];

    /**
     * @return Attribute<UserRole, UserRole|string|null>
     */
    protected function role(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): UserRole => $value !== null
                ? UserRole::from($value)
                : UserRole::Operator,
            set: fn (UserRole|string|null $value): string => match (true) {
                $value instanceof UserRole => $value->value,
                $value === null => UserRole::Operator->value,
                default => $value,
            },
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * @return HasMany<QueueTicket, $this>
     */
    public function createdQueueTickets(): HasMany
    {
        return $this->hasMany(QueueTicket::class, 'created_by_user_id');
    }

    /**
     * @return HasMany<QueueTicket, $this>
     */
    public function handledQueueTickets(): HasMany
    {
        return $this->hasMany(QueueTicket::class, 'handled_by_user_id');
    }

    /**
     * @return HasMany<QueueCall, $this>
     */
    public function queueCalls(): HasMany
    {
        return $this->hasMany(QueueCall::class);
    }
}
