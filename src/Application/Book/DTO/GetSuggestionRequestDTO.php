<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Application\Book\DTO;

class GetSuggestionRequestDTO
{
    public function __construct(
        public readonly int $suggestionId
    )
    {

    }
}
