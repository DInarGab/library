<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Application\Lending\DTO;

class ListLendingsRequestDTO
{
    public function __construct(
        public readonly int $page,
        public readonly int $limit,
        public readonly ?int $userId
    )
    {

    }
}
