<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Domain\Event\Events;

use DateTimeImmutable;

interface DomainEventInterface
{
    public function getEventName(): string;
    public function occurredAt(): DateTimeImmutable;
}
