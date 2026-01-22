<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Infrastructure\Bot\Service;

use Dinargab\LibraryBot\Infrastructure\Queue\Notification\MassNotificationMessage;
use Dinargab\LibraryBot\Infrastructure\Queue\Notification\NotificationMessage;
use Symfony\Component\Messenger\MessageBusInterface;

class NotificationService
{
    public function __construct(
        private readonly MessageBusInterface $messageBus
    ) {
    }

    public function notify(
        int $chatId,
        string $text,
        string $parseMode = 'Markdown',
    ): void {
        $message = new NotificationMessage(
            chatId: $chatId,
            text: $text,
            parseMode: $parseMode,
        );

        $this->messageBus->dispatch($message);
    }

    public function broadcast(
        array $chatIds,
        string $text,
    ): void {
        $message = new MassNotificationMessage(
            chatIds: $chatIds,
            text: $text,
        );

        $this->messageBus->dispatch($message);
    }

    /**
     * Уведомление о новой книге
     */
    public function notifyNewBook(int $chatId, string $bookTitle, string $author): void
    {
        $text = sprintf(
            "📚 <b>Новая книга добавлена!</b>\n\n" .
            "📖 %s\n" .
            "✍️ %s",
            htmlspecialchars($bookTitle),
            htmlspecialchars($author)
        );

        $this->notify($chatId, $text);
    }

    /**
     * Напоминание о возврате книги
     */
    public function notifyBookReturn(int $chatId, string $bookTitle, int $daysLeft): void
    {
        $text = sprintf(
            "⏰ <b>Напоминание о возврате</b>\n\n" .
            "Книгу \"%s\" нужно вернуть через %d %s",
            htmlspecialchars($bookTitle),
            $daysLeft,
            $this->pluralize($daysLeft, ['день', 'дня', 'дней'])
        );

        $this->notify($chatId, $text);
    }

    private function pluralize(int $n, array $forms): string
    {
        $n  = abs($n) % 100;
        $n1 = $n % 10;

        if ($n > 10 && $n < 20) {
            return $forms[2];
        }
        if ($n1 > 1 && $n1 < 5) {
            return $forms[1];
        }
        if ($n1 === 1) {
            return $forms[0];
        }

        return $forms[2];
    }

}
