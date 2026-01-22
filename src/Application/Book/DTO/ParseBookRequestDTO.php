<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Application\Book\DTO;

class ParseBookRequestDTO
{
    public function __construct(
        public string $url,
    ) {
    }
}
