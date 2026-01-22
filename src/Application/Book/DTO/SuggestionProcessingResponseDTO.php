<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Application\Book\DTO;

use Dinargab\LibraryBot\Application\Shared\DTO\SuggestionDTO;

class SuggestionProcessingResponseDTO
{

    public function __construct(
        public SuggestionDTO $suggestion,
    ) {
    }
}
