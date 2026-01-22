<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Application\User\Factory;

use Dinargab\LibraryBot\Domain\User\Entity\User;
use Dinargab\LibraryBot\Domain\User\Factory\UserFactoryInterface;
use Dinargab\LibraryBot\Domain\User\ValueObject\TelegramId;
use Dinargab\LibraryBot\Domain\User\ValueObject\UserRole;

class UserFactory implements UserFactoryInterface
{
    public function create(
        string $telegramId,
        ?string $username = null,
        ?string $firstName = null,
        ?string $lastName = null,
        bool $isAdmin = false
    ): User {
        return new User(
            telegramId: new TelegramId($telegramId),
            username: $username,
            firstName: $firstName,
            lastName: $lastName,
            role: $isAdmin ? UserRole::ADMIN : UserRole::USER
        );
    }
}
