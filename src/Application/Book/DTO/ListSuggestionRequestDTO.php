<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Application\Book\DTO;

class ListSuggestionRequestDTO
{
    public function __construct(
        public int $page,
        public int $limit,
        public ?int $userId = null
    )
    {

    }
}
