<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Application\User\DTO;

class GetUserInfoRequestDTO
{
    public function __construct(
        public int $userId,
    ) {
    }
}
