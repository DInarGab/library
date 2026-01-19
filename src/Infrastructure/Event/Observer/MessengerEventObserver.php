<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Infrastructure\Event\Observer;

use Dinargab\LibraryBot\Domain\Event\EventObserverInterface;
use Dinargab\LibraryBot\Domain\Event\Events\BookAddedEvent;
use Dinargab\LibraryBot\Domain\Event\Events\BookDeletedEvent;
use Dinargab\LibraryBot\Domain\Event\Events\BookLentEvent;
use Dinargab\LibraryBot\Domain\Event\Events\BookReturnedEvent;
use Dinargab\LibraryBot\Domain\Event\Events\DomainEventInterface;
use Dinargab\LibraryBot\Domain\Event\Events\LendingOverdueEvent;
use Dinargab\LibraryBot\Domain\Event\Events\SuggestionAddedEvent;
use Dinargab\LibraryBot\Domain\Event\Events\SuggestionProcessedEvent;
use Symfony\Component\Messenger\MessageBusInterface;

class MessengerEventObserver implements EventObserverInterface
{
    public function __construct(
        private readonly MessageBusInterface $messageBus
    ) {}

    public function getSubscribedEvents(): array
    {
        return [
            BookAddedEvent::class,
            BookDeletedEvent::class,
            BookLentEvent::class,
            BookReturnedEvent::class,
            LendingOverdueEvent::class,
            SuggestionProcessedEvent::class,
            SuggestionAddedEvent::class
        ];
    }

    public function handleEvent(DomainEventInterface $event): void
    {
        $this->messageBus->dispatch($event);
    }
}
