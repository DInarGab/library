<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Domain\Event\Events;

class BookReturnedEvent extends AbstractDomainEvent
{
    public function __construct(
        public readonly int $lendingId,
        public readonly string $bookAuthor,
        public readonly string $bookTitle,
        public readonly string $userName,
        public readonly string $userTelegramId,
        public readonly bool $wasOverdue
    ) {
        parent::__construct();
    }
}
