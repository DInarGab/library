<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Domain\Event;

use Dinargab\LibraryBot\Domain\Event\Events\DomainEventInterface;

interface EventObserverInterface
{
    public function getSubscribedEvents(): array;

    public function handleEvent(DomainEventInterface $event): void;
}
