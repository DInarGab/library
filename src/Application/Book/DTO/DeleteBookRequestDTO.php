<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Application\Book\DTO;

class DeleteBookRequestDTO
{
    public function __construct(
        public readonly int $bookId
    )
    {
    }
}