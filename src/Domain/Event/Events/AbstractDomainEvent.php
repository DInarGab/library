<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Domain\Event\Events;

use DateTimeImmutable;

class AbstractDomainEvent implements DomainEventInterface
{
    private DateTimeImmutable $occurredAt;

    public function __construct()
    {
        $this->occurredAt = new DateTimeImmutable();
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function getEventName(): string
    {
        return static::class;
    }

}
