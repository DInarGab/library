<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Infrastructure\Event;

use Dinargab\LibraryBot\Domain\Event\EventDispatcherInterface;
use Dinargab\LibraryBot\Domain\Event\EventObserverInterface;
use Dinargab\LibraryBot\Domain\Event\Events\DomainEventInterface;
use SplObjectStorage;

class EventDispatcher implements EventDispatcherInterface
{
    private SplObjectStorage $observers;

    public function __construct(
    )
    {
        $this->observers = new SplObjectStorage();
    }

    public function attach(EventObserverInterface $observer): void
    {
        $this->observers->attach($observer);
    }

    public function detach(EventObserverInterface $observer): void
    {
        $this->observers->detach($observer);
    }

    public function dispatch(DomainEventInterface $event): void
    {

        foreach ($this->observers as $observer) {
            if ($this->observerSupportsEvent($observer, $event)) {
                $observer->handleEvent($event);
            }
        }
    }

    private function observerSupportsEvent(
        EventObserverInterface $observer,
        DomainEventInterface   $event
    ): bool
    {
        $subscribedEvents = $observer->getSubscribedEvents();

        if (empty($subscribedEvents)) {
            return true;
        }

        foreach ($subscribedEvents as $eventClass) {
            if ($event instanceof $eventClass) {
                return true;
            }
        }

        return false;
    }
}
