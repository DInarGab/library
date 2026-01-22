<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Infrastructure\Bot\Commands;

use Dinargab\LibraryBot\Application\Lending\DTO\ListLendingsRequestDTO;
use Dinargab\LibraryBot\Application\Lending\UseCase\ListLendingsUseCase;
use Dinargab\LibraryBot\Application\Shared\DTO\LendingDTO;
use Dinargab\LibraryBot\Application\Shared\DTO\UserDTO;
use Dinargab\LibraryBot\Infrastructure\Bot\Service\KeyboardService;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;

class ListLendingsCommand
{

    private const PER_PAGE = 5;

    public const COMMAND_PREFIX = "list_lendings";

    public function __construct(
        private ListLendingsUseCase $getLendingsUseCase,
        private KeyboardService $paginationKeyboardService
    ) {
    }

    public function start()
    {
    }

    public function __invoke(Nutgram $bot, ?string $page = null): void
    {
        /** @var UserDTO $currentUser */
        $currentUser = $bot->get('user');


        $currentPage = $page !== null ? (int)$page : 1;

        $result = ($this->getLendingsUseCase)(
            new ListLendingsRequestDTO(
                page: $currentPage,
                limit: self::PER_PAGE,
                //Если админ возвращаем все lendingи, если пользователь то только пользователя
                userId: $currentUser->isAdmin ? null : $currentUser->id
            )
        );

        $text = $this->formatLendingsList($result->lendings, $currentPage);

        $keyboard = $this->paginationKeyboardService->createListWithPagination(
            items: $result->lendings,
            currentPage: $currentPage,
            totalPages: $result->maxPage,
            itemCallback: fn(LendingDTO $lending, $key) => InlineKeyboardButton::make(
                text: ($key + 1) . ") $lending->userName: $lending->statusDisplayValue - $lending->bookAuthor \"$lending->bookTitle\"\n Вернуть до: $lending->dueDate",
                callback_data: LendingsDetailPageCommand::COMMAND_PREFIX . ":{$lending->id}"
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


    public function formatLendingsList(array $lendings, int $pageNumber): string
    {
        if (empty($lendings)) {
            return "*Нет выданных книг*\n\n";
        }

        return "*Список выдач* (страница {$pageNumber})\n\n";
    }
}
