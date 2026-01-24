<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Infrastructure\Bot\Commands;

use Dinargab\LibraryBot\Application\Lending\DTO\GetLendingRequestDTO;
use Dinargab\LibraryBot\Application\Lending\UseCase\GetLendingUseCase;
use Dinargab\LibraryBot\Application\Shared\DTO\LendingDTO;
use Dinargab\LibraryBot\Application\Shared\DTO\UserDTO;
use Dinargab\LibraryBot\Domain\Exception\LendingNotFoundException;
use Dinargab\LibraryBot\Domain\Lending\ValueObject\LendingStatus;
use Dinargab\LibraryBot\Domain\User\Entity\User;
use Dinargab\LibraryBot\Infrastructure\Bot\Commands\Admin\ReturnBookCommand;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

class LendingsDetailPageCommand
{
    private Nutgram $bot;
    private UserDTO $user;

    public const COMMAND_PREFIX = 'lending_detail';

    public function __construct(
        private GetLendingUseCase $getLendingUseCase,
    ) {
    }

    public function __invoke(Nutgram $bot, string $lendingId)
    {
        $this->bot = $bot;
        /** @var User $user */
        $this->user = $this->bot->get('user');
        try {
            $lendingInfo = ($this->getLendingUseCase)(new GetLendingRequestDTO((int)$lendingId));
        } catch (LendingNotFoundException $exception) {
            $bot->sendMessage($exception->getMessage());

            return;
        }

        $message = $bot->callbackQuery()?->message;
        $text    = "Автор: $lendingInfo->bookAuthor \n"
                   . "Книга: $lendingInfo->bookTitle \n"
                   . "Книга выдана: $lendingInfo->issuedAt \n"
                   . "К возврату: $lendingInfo->dueDate \n";

        if ($this->user->isAdmin) {
            $text .= "Выдана пользователю: $lendingInfo->userName";
        }
        $keyboard = $this->createBookLendingKeyboard($lendingInfo);
        $bot->editMessageText(
            text: $text,
            chat_id: $message->chat->id,
            message_id: $message->message_id,
            reply_markup: $keyboard,
            parse_mode: 'Markdown'
        );
    }

    private function createBookLendingKeyboard(LendingDTO $lendingDetail): InlineKeyboardMarkup
    {
        $keyboard = InlineKeyboardMarkup::make();
        $bookBorowedOrOverdue = $lendingDetail->status === LendingStatus::LENT->value || $lendingDetail->status === LendingStatus::OVERDUE->value;
        if ($this->user->isAdmin && $bookBorowedOrOverdue) {
            $keyboard->addRow(
                InlineKeyboardButton::make(
                    'Вернуть книгу',
                    callback_data: ReturnBookCommand::RETURN_BOOK_PREFIX . ":$lendingDetail->id"
                )
            );
        }
        $keyboard->addRow(
            InlineKeyboardButton::make("Назад", callback_data: ListLendingsCommand::COMMAND_PREFIX . ":1"),
            InlineKeyboardButton::make("❌ Закрыть", callback_data: "close")
        );

        return $keyboard;
    }
}
