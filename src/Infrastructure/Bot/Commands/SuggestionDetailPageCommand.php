<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Infrastructure\Bot\Commands;

use Dinargab\LibraryBot\Application\Book\DTO\GetSuggestionRequestDTO;
use Dinargab\LibraryBot\Application\Book\UseCase\GetSuggestionUseCase;
use Dinargab\LibraryBot\Application\Shared\DTO\SuggestionDTO;
use Dinargab\LibraryBot\Application\Shared\DTO\UserDTO;
use Dinargab\LibraryBot\Domain\Book\ValueObject\BookSuggestionStatus;
use Dinargab\LibraryBot\Domain\Exception\BookSuggestionNotFoundException;
use Dinargab\LibraryBot\Infrastructure\Bot\Commands\Admin\SuggestionProcessingCommand;
use Dinargab\LibraryBot\Infrastructure\Bot\Service\KeyboardService;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

class SuggestionDetailPageCommand
{
    public const SUGGESTION_DETAIL_CALLBACK = 'suggestion_detail';
    private Nutgram $bot;

    public function __construct(
        private KeyboardService $keyboardService,
        private GetSuggestionUseCase $getSuggestionUseCase,
    ) {
    }

    public function __invoke(Nutgram $bot, string $suggestionId): void
    {
        $this->bot = $bot;
        try {
            $bookDetail = ($this->getSuggestionUseCase)(new GetSuggestionRequestDTO((int)$suggestionId));
        } catch (BookSuggestionNotFoundException $exception) {
            $bot->sendMessage('Предложение не найдено');

            return;
        }

        $text     = $this->formatBookDetailText($bookDetail);
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

    private function formatBookDetailText(SuggestionDTO $suggestionDTO): string
    {
        $text = "*{$suggestionDTO->title}*\n\n";
        $text .= "*Автор:* {$suggestionDTO->author}\n";

        if ($suggestionDTO->isbn) {
            $text .= "*ISBN:* `{$suggestionDTO->isbn}`\n";
        }
        if ($suggestionDTO->sourceUrl) {
            $text .= "*Ссылка:* {$suggestionDTO->sourceUrl}\n";
        }
        $text .= "*Комментарий предложившего:* $suggestionDTO->comment\n";
        if ($suggestionDTO->adminComment && $suggestionDTO->status === BookSuggestionStatus::REJECTED->value) {
            $text .= "*Причина отказа*: $suggestionDTO->adminComment\n";
        }
        if ($suggestionDTO->adminComment && $suggestionDTO->status === BookSuggestionStatus::APPROVED->value) {
            $text .= "*Комментарий администратора*: $suggestionDTO->adminComment\n";
        }
        $text .= "*Предложил:* $suggestionDTO->userName\n";
        $text .= "*Дата предложения*: $suggestionDTO->createdAt\n";


        return $text;
    }

    private function createBookDetailKeyboard(SuggestionDTO $suggestionDTO): InlineKeyboardMarkup
    {
        /** @var UserDTO $user */
        $user             = $this->bot->get('user');
        $additionalButton = [];

        if ($user->isAdmin) {
            $additionalButton = [
                InlineKeyboardButton::make(
                    'Принять предложение',
                    callback_data: SuggestionProcessingCommand::PROCESS_SUGGESTION_CALLBACK . ":$suggestionDTO->id:" . BookSuggestionStatus::APPROVED->value
                ),
                InlineKeyboardButton::make(
                    'Отклонить предложение',
                    callback_data: SuggestionProcessingCommand::PROCESS_SUGGESTION_CALLBACK . ":$suggestionDTO->id:" . BookSuggestionStatus::REJECTED->value
                ),
            ];
        }

        return $this->keyboardService->createNavigationKeyboard(
            backCallback: ListSuggestionsCommand::COMMAND_PREFIX . ":1",
            additionalButtons: $additionalButton
        );
    }

}
