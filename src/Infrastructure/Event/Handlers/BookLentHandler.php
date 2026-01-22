<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Infrastructure\Event\Handlers;

use Dinargab\LibraryBot\Domain\Event\Events\BookLentEvent;
use Dinargab\LibraryBot\Infrastructure\Notification\NotificationServiceInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class BookLentHandler
{
    public function __construct(
        private readonly NotificationServiceInterface $notificationService,
    ) {
    }

    public function __invoke(BookLentEvent $event): void
    {
        // Уведомление пользователю о выдаче
        $this->notificationService->notifyUser(
            $event->userTelegramId,
            "Вам выдана книга\n\n" .
            "*$event->bookAuthor* \n" .
            "*$event->bookTitle*\n" .
            "Вернуть до: {$event->dueDate->format('d.m.Y')}\n\n" .
            "Пожалуйста, верните книгу вовремя!"
        );
    }
}
