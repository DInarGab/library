<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Infrastructure\Notification;

interface NotificationServiceInterface
{
    public function notifyUser(string $chatId, string $message): void;
    public function notifyAllUsers(string $message): void;
    public function notifyAdmin(string $message): void;
}
