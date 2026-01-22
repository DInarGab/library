<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Application\Book\DTO;

class AddBookRequestDTO
{
    public function __construct(
        public readonly string $title,
        public readonly string $author,
        public readonly int $copies = 1,
        public readonly string $isbn = '',
        public readonly ?string $description = null,
        public readonly ?string $coverUrl = null
    ) {
    }
}
