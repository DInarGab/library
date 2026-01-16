<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Infrastructure\Bot\Commands;

use Dinargab\LibraryBot\Application\Book\DTO\GetBookRequestDTO;
use Dinargab\LibraryBot\Application\Book\UseCase\GetBookUseCase;
use Dinargab\LibraryBot\Application\Shared\DTO\BookDTO;
use Dinargab\LibraryBot\Application\Shared\DTO\UserDTO;
use Dinargab\LibraryBot\Domain\Exception\BookNotFoundException;
use Dinargab\LibraryBot\Infrastructure\Bot\Service\KeyboardService;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

class BookDetailPageCommand
{
    private Nutgram $bot;
    public function __construct(
        private GetBookUseCase  $useCase,
        private KeyboardService $paginationKeyboardService,
    )
    {

    }


    public function __invoke(Nutgram $bot, string $bookId): void
    {
        $this->bot = $bot;
        try {
            $bookDetail = ($this->useCase)(new GetBookRequestDTO((int)$bookId));
        } catch (BookNotFoundException $exception) {
            $bot->sendMessage('Книга не найдена');
            return;
        }

        $text = $this->formatBookDetailText($bookDetail);
        $keyboard = $this->createBookDetailKeyboard($bookDetail);

        $message = $bot->callbackQuery()?->message;

        $bot->editMessageText(
            text: $text,
            chat_id: $message->chat->id,
            message_id: $message->message_id,
            reply_markup: $keyboard,
            parse_mode: 'Markdown'
        );
    }

    private function createBookDetailKeyboard(BookDTO $bookDetail): InlineKeyboardMarkup
    {
        /** @var UserDTO $user */
        $user = $this->bot->get('user');
        $additionalButton = [];

        if ($user->isAdmin) {
            $additionalButton = [
                InlineKeyboardButton::make('Выдать книгу', callback_data: "lend_book:$bookDetail->id"),
                InlineKeyboardButton::make('Удалить книгу', callback_data: "delete_book:$bookDetail->id"),
            ];

        }

        return $this->paginationKeyboardService->createNavigationKeyboard(
            backCallback: ListBooksCommand::PAGINATION_PREFIX . ":1",
            additionalButtons: $additionalButton
        );
    }

    private function formatBookDetailText(BookDTO $bookDetail): string
    {
        $text = "*{$bookDetail->title}*\n\n";
        $text .= "*Автор:* {$bookDetail->author}\n";

        if ($bookDetail->isbn) {
            $text .= "*ISBN:* `{$bookDetail->isbn}`\n";
        }


        $text .= "\n *Описание:*\n{$bookDetail->description}\n\n";

        $text .= "*Доступно копий:* $bookDetail->availableCopies\n";

        return $text;
    }

}
