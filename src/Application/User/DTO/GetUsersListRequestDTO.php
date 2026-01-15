<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Application\User\DTO;

class GetUsersListRequestDTO
{
    public function __construct(
        public readonly int $page,
        public readonly int $limit
    )
    {

    }
}
