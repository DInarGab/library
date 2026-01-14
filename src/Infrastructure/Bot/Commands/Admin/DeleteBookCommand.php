<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Infrastructure\Bot\Commands\Admin;

use Dinargab\LibraryBot\Application\Book\DTO\DeleteBookRequestDTO;
use Dinargab\LibraryBot\Application\Book\UseCase\DeleteBookUseCase;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

class DeleteBookCommand
{

    public function __construct(
        private DeleteBookUseCase $deleteBookUseCase
    )
    {

    }
    public function __invoke(Nutgram $bot, $bookId)
    {
        try {
            ($this->deleteBookUseCase)(new DeleteBookRequestDTO((int)$bookId));
        } catch (\Exception $exception) {
            $bot->sendMessage($exception->getMessage());
            return;
        }

        $bot->sendMessage(
            "Книга успешно удалена",
            reply_markup: InlineKeyboardMarkup::make()->addRow(
                InlineKeyboardButton::make('❌ Закрыть', callback_data: 'close')
            )
        );
    }
}
