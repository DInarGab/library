<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Domain\Event\Events;

use DateTimeImmutable;

class BookLentEvent extends AbstractDomainEvent
{
    public function __construct(
        public readonly int               $lendingId,
        public readonly int               $bookId,
        public readonly string            $bookAuthor,
        public readonly string            $bookTitle,
        public readonly int               $bookCopyId,
        public readonly string            $inventoryNumber,
        public readonly string            $userTelegramId,
        public readonly DateTimeImmutable $dueDate
    )
    {
        parent::__construct();
    }
}
