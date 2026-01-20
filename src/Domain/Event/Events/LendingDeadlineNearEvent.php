<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Domain\Event\Events;

class LendingDeadlineNearEvent extends AbstractDomainEvent
{
    public function __construct(
        public readonly string $bookAuthor,
        public readonly string $bookTitle,
        public readonly string $userTelegramId,
        public readonly int    $daysBeforeDue,
        public readonly \DateTimeImmutable $dueDate,
    )
    {
        parent::__construct();
    }
}
