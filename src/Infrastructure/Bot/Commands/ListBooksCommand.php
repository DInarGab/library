<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Infrastructure\Bot\Commands;

use Dinargab\LibraryBot\Application\Book\DTO\ListBooksRequestDTO;
use Dinargab\LibraryBot\Application\Book\UseCase\ListAvailableBooksUseCase;
use Dinargab\LibraryBot\Application\Shared\DTO\BookDTO;
use Dinargab\LibraryBot\Infrastructure\Bot\Service\KeyboardService;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;

class ListBooksCommand
{
    public const COMMAND_PREFIX = "list_books";
    private const PER_PAGE = 5;

    public function __construct(
        private ListAvailableBooksUseCase $listAvailableBooksUseCase,
        private KeyboardService $paginationKeyboardService
    ) {
    }

    public function __invoke(Nutgram $bot, ?string $page = null): void
    {
        $currentPage = $page !== null ? (int)$page : 1;

        $result = ($this->listAvailableBooksUseCase)(
            new ListBooksRequestDTO(
                page: $currentPage,
                limit: self::PER_PAGE,
            )
        );

        $text = $this->formatBooksList($result->books, $currentPage);

        $keyboard = $this->paginationKeyboardService->createListWithPagination(
            items: $result->books,
            currentPage: $currentPage,
            totalPages: $result->maxPage,
            itemCallback: fn(BookDTO $book, $key) => InlineKeyboardButton::make(
                text: ($key + 1) . ") $book->author \"$book->title\"",
                callback_data: BookDetailPageCommand::COMMAND_PREFIX . ":$book->id"
            ),
            paginationCallbackPrefix: self::COMMAND_PREFIX,
        );

        if ($bot->callbackQuery() !== null) {
            $message = $bot->callbackQuery()->message;

            $bot->editMessageText(
                text: $text,
                chat_id: $message->chat->id,
                message_id: $message->message_id,
                reply_markup: $keyboard,
                parse_mode: 'Markdown'
            );

            $bot->answerCallbackQuery();
        } else {
            $bot->sendMessage(
                text: $text,
                reply_markup: $keyboard,
                parse_mode: 'Markdown'
            );
        }
    }

    private function formatBooksList(array $books, int $page): string
    {
        if (empty($books)) {
            return "*Библиотека пуста*\n\nКниги пока не добавлены.";
        }

        $text = "*Список книг* (страница {$page})\n\n";
        $text .= "Выберите книгу для подробной информации:";

        return $text;
    }


}


