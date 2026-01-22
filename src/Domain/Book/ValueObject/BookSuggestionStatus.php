<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Domain\Book\ValueObject;

enum BookSuggestionStatus: string
{
    case PENDING = "pending";
    case APPROVED = "approved";
    case REJECTED = "rejected";
}
