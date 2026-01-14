<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Application\Book\DTO;

class ListBooksResponseDTO
{
    public function __construct(
        public readonly array $books,
        public readonly int $page,
        public readonly int $maxPage,
    )
    {

    }
}
