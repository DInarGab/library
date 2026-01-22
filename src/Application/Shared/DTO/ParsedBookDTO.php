<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Application\Shared\DTO;

class ParsedBookDTO
{
    public function __construct(
        public readonly string $title,
        public readonly string $description,
        public readonly string $url,
        public readonly string $author,
        public readonly string $isbn
    ) {
    }
}
