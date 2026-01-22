<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Infrastructure\Bot\Commands\Admin;

use Dinargab\LibraryBot\Application\Book\DTO\DeleteBookRequestDTO;
use Dinargab\LibraryBot\Application\Book\UseCase\DeleteBookUseCase;
use DomainException;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

class DeleteBookCommand
{

    public const COMMAND_PREFIX = 'delete_book';

    public function __construct(
        private DeleteBookUseCase $deleteBookUseCase
    ) {
    }

    public function __invoke(Nutgram $bot, $bookId)
    {
        $message = $bot->callbackQuery()?->message;

        $messageText = "Книга успешно удалена";
        try {
            ($this->deleteBookUseCase)(new DeleteBookRequestDTO((int)$bookId));
        } catch (DomainException $exception) {
            $messageText = $exception->getMessage();
        }

        $bot->editMessageText(
            $messageText,
            $message->chat->id,
            $message->message_id,
            reply_markup: InlineKeyboardMarkup::make()->addRow(
                InlineKeyboardButton::make('❌ Закрыть', callback_data: 'close')
            )
        );
    }
}
