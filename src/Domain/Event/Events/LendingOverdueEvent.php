<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Domain\Event\Events;

class LendingOverdueEvent extends AbstractDomainEvent
{
    public function __construct(
        public readonly int    $lendingId,
        public readonly int    $bookId,
        public readonly string $bookTitle,
        public readonly int    $userId,
        public readonly string $userTelegramId,
        public readonly int    $daysOverdue
    )
    {
        parent::__construct();
    }
}
