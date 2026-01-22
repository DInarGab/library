<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Infrastructure\Event\Handlers;

use Dinargab\LibraryBot\Domain\Event\Events\SuggestionAddedEvent;
use Dinargab\LibraryBot\Infrastructure\Notification\NotificationServiceInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class SuggestionAddedHandler
{
    public function __construct(
        public readonly NotificationServiceInterface $notificationService,
    ) {
    }

    public function __invoke(SuggestionAddedEvent $event): void
    {
        $this->notificationService->notifyAdmin(
            "*Добавлено новое предложение:* \n\n" .
            "Книга: $event->bookAuthor - $event->bookTitle \n" .
            "Ссылка: $event->sourceUrl \n" .
            "Комментарий предложивашего: $event->comment \n" .
            "Автор предложения: $event->userName"
        );
    }
}
