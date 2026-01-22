<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Application\Book\DTO;

use Dinargab\LibraryBot\Domain\Book\ValueObject\BookSuggestionStatus;

class SuggestionProcessingRequestDTO
{
    public function __construct(
        public readonly int $suggestionId,
        public readonly ?string $adminComment,
        public readonly BookSuggestionStatus $suggestionStatus,
    ) {
    }
}
