<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Application\User\DTO;

class GetUsersListResponseDTO
{
    public function __construct(
        public readonly array $users,
        public readonly int $page,
        public readonly int $totalPages,
    ) {
    }
}
