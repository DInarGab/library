<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Infrastructure\Event\Handlers;

use Dinargab\LibraryBot\Domain\Event\Events\BookAddedEvent;
use Dinargab\LibraryBot\Infrastructure\Notification\NotificationServiceInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class BookAddedHandler
{
    public function __construct(
        private readonly NotificationServiceInterface $notificationService,
    ) {
    }

    public function __invoke(BookAddedEvent $event): void
    {
        //Уведомляем всех о новой книге
        $this->notificationService->notifyAllUsers(
            "Добавлена новая книга\n\n" .
            "*$event->title*\n" .
            "Автор: {$event->author}\n" .
            ($event->isbn ? "ISBN: {$event->isbn}\n" : "")
        );
    }
}
