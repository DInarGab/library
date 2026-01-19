<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Infrastructure\Event\Handlers;

use Dinargab\LibraryBot\Domain\Event\Events\SuggestionAddedEvent;
use Dinargab\LibraryBot\Infrastructure\Notification\NotificationServiceInterface;

class SuggestionAddedHandler
{
    public function __construct(
        public readonly NotificationServiceInterface $notificationService,
    )
    {

    }

    public function __invoke(SuggestionAddedEvent $event): void
    {
        $this->notificationService->notifyAdmin("*Добавлено новое предложение:*" .
            "Книга: $event->bookAuthor - $event->bookTitle" .
            "Ссылка: $event->sourceUrl" .
            "Комментарий предложивашего" .
            "Автор предложения: $event->userName");
    }
}
