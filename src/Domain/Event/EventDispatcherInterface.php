<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Domain\Event;

use Dinargab\LibraryBot\Domain\Event\Events\DomainEventInterface;

interface EventDispatcherInterface
{
    public function detach(EventObserverInterface $observer): void;

    public function attach(EventObserverInterface $observer): void;

    public function dispatch(DomainEventInterface $event): void;
}
