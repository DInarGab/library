<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Domain\Event\Events;

class SuggestionAddedEvent extends AbstractDomainEvent
{
    public function __construct(
        public readonly string $bookAuthor,
        public readonly string $bookTitle,
        public readonly string $userName,
        public readonly ?string $comment,
        public readonly ?string $sourceUrl,
    )
    {
        parent::__construct();
    }
}
