<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Infrastructure\Event\Handlers;

use Dinargab\LibraryBot\Domain\Event\Events\BookReturnedEvent;
use Dinargab\LibraryBot\Infrastructure\Notification\NotificationServiceInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class BookReturnedHandler
{
    public function __construct(
        private readonly NotificationServiceInterface $notificationService,
    )
    {
    }

    public function __invoke(BookReturnedEvent $event): void
    {

        $message = "Книга возвращена\n\n" .
            "*$event->bookAuthor*\n" .
            "*$event->bookTitle*\n";

        if ($event->wasOverdue) {
            $message .= "\nКнига была возвращена с просрочкой.";
        } else {
            $message .= "\nСпасибо за своевременный возврат.";
        }

        $this->notificationService->notifyUser(
            $event->userTelegramId,
            $message
        );
    }
}
