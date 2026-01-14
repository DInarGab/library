<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Application\User\DTO;

class GetOrCreateUserRequestDTO
{
    public function __construct(
        public readonly int $telegramId,
        public readonly ?string $username = null,
        public readonly ?string $firstName = null,
        public readonly ?string $lastName = null,
        public readonly ?bool $isAdmin = false,
    )
    {

    }
}
