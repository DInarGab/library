<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Application\Book\DTO;

class SuggestBookRequestDTO
{
    public function __construct(
        public readonly int $telegramId,
        public readonly string $url,
    ) {

    }
}