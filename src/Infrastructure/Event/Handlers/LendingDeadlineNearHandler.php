<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Infrastructure\Event\Handlers;

use Dinargab\LibraryBot\Domain\Event\Events\LendingDeadlineNearEvent;
use Dinargab\LibraryBot\Infrastructure\Notification\NotificationServiceInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class LendingDeadlineNearHandler
{
    public function __construct(
        private NotificationServiceInterface $notificationService
    )
    {

    }

    public function __invoke(LendingDeadlineNearEvent $event): void
    {
        $message = "*Приближается окончание срока выдачи книги:*\n\n" .
            "Автор: $event->bookAuthor \n" .
            "Название: $event->bookTitle \n" .
            "Крайний срок: $event->dueDate";

        $this->notificationService->notifyUser($event->userTelegramId, $message);
    }
}
