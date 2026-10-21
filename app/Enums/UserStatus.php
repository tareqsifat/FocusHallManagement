<?php

namespace App\Enums;

enum UserStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Rejected = 'rejected';
    case Inactive = 'inactive';

    /** Why an account with this status cannot enter protected areas. */
    public function loginMessage(?string $reason = null): string
    {
        return match ($this) {
            self::Pending => 'Your registration is awaiting approval by the hall office.',
            self::Rejected => 'Your registration was rejected'.($reason ? ": {$reason}" : '.'),
            self::Inactive => 'Your account has been deactivated.',
            self::Active => '',
        };
    }
}
