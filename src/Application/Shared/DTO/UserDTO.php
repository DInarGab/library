<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Application\Shared\DTO;

use Dinargab\LibraryBot\Domain\Book\Entity\Book;
use Dinargab\LibraryBot\Domain\User\Entity\User;

class UserDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $displayName,
        public readonly ?string $username,
        public readonly int $telegramId,
        public readonly ?bool $isAdmin
    )
    {

    }

    public static function fromEntity(User $user): self
    {
        return new UserDTO(
            $user->getId(),
            $user->getDisplayName(),
            $user->getUsername(),
            $user->getTelegramId()->getValue(),
            $user->isAdmin(),
        );
    }
}
