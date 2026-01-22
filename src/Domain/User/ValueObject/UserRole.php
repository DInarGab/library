<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Domain\User\ValueObject;

enum UserRole: string
{
    case USER = 'user';
    case ADMIN = 'admin';

    public function isAdmin(): bool
    {
        return $this === self::ADMIN;
    }

}
