<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Application\Lending\DTO;

class LendingRequestDTO
{
    public function __construct(
        public readonly int $bookId,
        public readonly int $userTelegramId,
        public readonly ?int $daysToReturn = null
    )
    {

    }
}