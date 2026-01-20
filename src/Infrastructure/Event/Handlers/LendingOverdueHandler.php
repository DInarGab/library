<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Infrastructure\Event\Handlers;

use Dinargab\LibraryBot\Domain\Event\Events\LendingOverdueEvent;
use Dinargab\LibraryBot\Infrastructure\Notification\NotificationServiceInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class LendingOverdueHandler
{
    public function __construct(
        private NotificationServiceInterface $notificationService,
    )
    {

    }

    public function __invoke(LendingOverdueEvent $event): void
    {
        $message = "Выдача просрочена, пожалуйста, верните книгу:" .
            "Автор: $event->bookAuthor \n" .
            "Название: $event->bookTitle \n" .
            "Просрочка: $event->daysOverdue";

        $this->notificationService->notifyUser($event->userTelegramId, $message);
    }
}
