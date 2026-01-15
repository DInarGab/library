<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Infrastructure\Bot\Commands\Admin;

use Dinargab\LibraryBot\Application\Lending\DTO\ReturnBookRequestDTO;
use Dinargab\LibraryBot\Application\Lending\UseCase\ReturnBookUseCase;
use Dinargab\LibraryBot\Infrastructure\Bot\Commands\ListLendingsCommand;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

class ReturnBookCommand
{

    public const RETURN_BOOK_PREFIX = 'return_book:';
    public function __construct(
        private ReturnBookUseCase $useCase,
    )
    {

    }

    public function __invoke(Nutgram $bot, string $lendingId)
    {
        $lendingInfo = ($this->useCase)(new ReturnBookRequestDTO((int)$lendingId));


        $message = $bot->callbackQuery()?->message;
        $text = "*Книга возвращена* \n"
            . "Автор: $lendingInfo->bookAuthor \n"
            . "Книга: $lendingInfo->bookTitle \n"
            . "Книга выдана: $lendingInfo->issuedAt \n"
            . "Возращена: $lendingInfo->dueDate \n";
        $keyboard = InlineKeyboardMarkup::make();
        $keyboard->addRow(
            InlineKeyboardButton::make("Назад", callback_data: ListLendingsCommand::PAGINATION_PREFIX . ":1"),
            InlineKeyboardButton::make("Закрыть", callback_data: "close")
        );
        $bot->editMessageText(
            text: $text,
            chat_id: $message->chat->id,
            message_id: $message->message_id,
            reply_markup: $keyboard,
            parse_mode: 'Markdown'
        );
    }
}
