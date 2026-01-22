<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Infrastructure\Notification;

use Dinargab\LibraryBot\Application\User\DTO\GetUsersListRequestDTO;
use Dinargab\LibraryBot\Application\User\UseCase\GetUsersListUseCase;
use SergiX44\Nutgram\Nutgram;

class TelegramNotificationService implements NotificationServiceInterface
{
    public function __construct(
        private readonly Nutgram $bot,
        private readonly GetUsersListUseCase $getUsersListUseCase,
    ) {
    }

    public function notifyUser(string $chatId, string $message): void
    {
        $this->bot->sendMessage(
            text: $message,
            chat_id: (int)$chatId,
            parse_mode: "Markdown",
        );
    }

    public function notifyAllUsers(string $message): void
    {
        $page  = 1;
        $limit = 30;
        do {
            $usersList = ($this->getUsersListUseCase)(new GetUsersListRequestDTO($page, $limit));
            $page++;

            foreach ($usersList->users as $userDto) {
                $this->notifyUser($userDto->telegramId, $message);
            }
            $totalPages = $usersList->totalPages;
            //Спим секунду, чтобы не нарваться на лимиты
            usleep(1000);
        } while ($page < $totalPages);
    }

    public function notifyAdmin(string $message): void
    {
        $adminId = getenv('TELEGRAM_ADMIN_ID');
        $this->notifyUser($adminId, $message);
    }
}
