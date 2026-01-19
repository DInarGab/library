<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Domain\Event\Events;

class BookDeletedEvent extends AbstractDomainEvent
{
    public function __construct(
        public readonly int $bookId,
        public readonly string $title,
        public readonly string $author
    ) {
        parent::__construct();
    }
}
