<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Application\Book\DTO;

class SuggestBookRequestDTO
{
    public function __construct(
        public readonly int $userId,
        public readonly ?string $url,
        public readonly ?string $isbn,
        public readonly string $title,
        public readonly string $author,
        public readonly ?string $comment,
    ) {

    }
}
