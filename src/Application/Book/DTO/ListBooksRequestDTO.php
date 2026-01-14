<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Application\Book\DTO;

class ListBooksRequestDTO
{
    public function __construct(
        public readonly int $page,
        public readonly int $limit
    )
    {

    }
}
