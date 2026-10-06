<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Operator = 'operator';

    public function canAccessAdmin(): bool
    {
        return $this === self::Admin;
    }

    public function canAccessOperator(): bool
    {
        return in_array($this, [self::Admin, self::Operator], true);
    }
}
