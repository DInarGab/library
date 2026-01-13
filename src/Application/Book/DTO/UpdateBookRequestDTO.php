<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Application\Book\DTO;

class UpdateBookRequestDTO
{
    public function __construct(
        public readonly int $bookId,
        public readonly string $title,
        public readonly string $author,
        public readonly ?string $isbn = null,
        public readonly ?string $description = null,
        public readonly ?string $coverUrl = null
    )
    {

    }
}