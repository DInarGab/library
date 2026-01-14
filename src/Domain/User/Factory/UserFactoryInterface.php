<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Domain\User\Factory;

use Dinargab\LibraryBot\Domain\User\Entity\User;

interface UserFactoryInterface
{
    public function create(int $telegramId, ?string $username = null, ?string $firstName = null, ?string $lastName = null, bool $isAdmin = false): User;
}
