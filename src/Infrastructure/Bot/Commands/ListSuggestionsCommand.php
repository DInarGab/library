<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Infrastructure\Bot\Commands;

use Dinargab\LibraryBot\Application\Book\DTO\ListSuggestionRequestDTO;
use Dinargab\LibraryBot\Application\Book\UseCase\ListSuggestionsUseCase;
use Dinargab\LibraryBot\Application\Shared\DTO\SuggestionDTO;
use Dinargab\LibraryBot\Infrastructure\Bot\Service\KeyboardService;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;

class ListSuggestionsCommand
{

    public const COMMAND_PREFIX = "list_suggestions";

    public const PER_PAGE = 10;

    public function __construct(
        private ListSuggestionsUseCase $listSuggestionsUseCase,
        private KeyboardService $paginationKeyboardService,
    ) {
    }


    public function __invoke(Nutgram $bot, ?string $page = null): void
    {
        $currentPage = $page !== null ? (int)$page : 1;

        $result = ($this->listSuggestionsUseCase)(
            new ListSuggestionRequestDTO(
                page: $currentPage,
                limit: self::PER_PAGE,
            )
        );

        $text = $this->formatBooksList($result->suggestions, $currentPage);

        $keyboard = $this->paginationKeyboardService->createListWithPagination(
            items: $result->suggestions,
            currentPage: $currentPage,
            totalPages: $result->maxPage,
            itemCallback: fn(SuggestionDTO $suggestion, $key) => InlineKeyboardButton::make(
                text: ($key + 1) . ") " . $suggestion->title . " : " . $suggestion->author,
                callback_data: SuggestionDetailPageCommand::SUGGESTION_DETAIL_CALLBACK . ":$suggestion->id"
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
            return "*Предложений нет*";
        }

        $text = "*Список предложений* (страница {$page})\n\n";
        $text .= "Выберите предложение для подробной информации:";

        return $text;
    }

}
