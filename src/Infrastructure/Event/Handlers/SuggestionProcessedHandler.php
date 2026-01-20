<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Infrastructure\Event\Handlers;

use Dinargab\LibraryBot\Domain\Event\Events\BookAddedEvent;
use Dinargab\LibraryBot\Domain\Event\Events\SuggestionProcessedEvent;
use Dinargab\LibraryBot\Infrastructure\Notification\NotificationServiceInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class SuggestionProcessedHandler
{
    public function __construct(
        private readonly NotificationServiceInterface $notificationService,

    )
    {
    }

    public function __invoke(SuggestionProcessedEvent $event): void
    {
        $message = "*Ваше предложение:*\n\n" .
            "*Автор:* $event->bookAuthor \n" .
            "*Название: * $event->bookTitle \n\n" .
            "*Комментарий администратора:* $event->adminComment \n";

        if ($event->srcUrl) {
            $message .= 'Ссылка: ' . $event->srcUrl . "\n";
        }

        if ($event->approved) {
            $message .= "*Принято, в скором времени книга появится в библиотеке, следите за обновлениями*";
        } else {
            $message .= "*Отклонено*";
        }

        $this->notificationService->notifyUser(
            $event->userTelegramId,
            $message
        );
    }
}
