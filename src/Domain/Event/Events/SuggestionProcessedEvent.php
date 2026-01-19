<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Domain\Event\Events;

class SuggestionProcessedEvent extends AbstractDomainEvent
{
    public function __construct(
        public readonly string $userTelegramId,
        public readonly string $bookAuthor,
        public readonly string $bookTitle,
        public readonly string $srcUrl,
        public readonly bool $approved,
        public readonly string $adminComment
    )
    {
        parent::__construct();
    }
}
