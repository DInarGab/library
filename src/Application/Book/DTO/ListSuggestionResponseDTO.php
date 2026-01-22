<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Application\Book\DTO;

class ListSuggestionResponseDTO
{
    public function __construct(
        public array $suggestions,
        public int $page,
        public int $maxPage,
    ) {
    }
}
