<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Infrastructure\Bot\Commands;

use Dinargab\LibraryBot\Infrastructure\Bot\Service\KeyboardService;
use Exception;
use SergiX44\Nutgram\Conversations\Conversation;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;
use Symfony\Contracts\Service\Attribute\Required;

abstract class BaseConversation extends Conversation
{


    public function __construct(
        protected KeyboardService $keyboardService,
    )
    {

    }

    /**
     * Префикс для callback_data (должен быть уникальным для каждой команды)
     */
    abstract protected function getCallbackPrefix(): string;


    /**
     * Сброс состояния диалога
     */
    abstract protected function resetState(): void;

    /**
     * Получение данных для подтверждения
     */
    abstract protected function getConfirmationData(): array;

    /**
     * Сохранение результата
     */
    abstract protected function save(Nutgram $bot): void;

    /**
     * Показать подтверждение
     */
    protected function showConfirmation(Nutgram $bot): void
    {
        $data = $this->getConfirmationData();
        $message = $this->formatConfirmationMessage($data);
        $this->editOrSendMessage(
            bot: $bot,
            text: $message,
            keyboard: $this->keyboardService->buildConfirmationKeyboard($this->getCallbackPrefix())
        );

        $this->next('handleConfirmation');
    }

    /**
     * Форматирование сообщения подтверждения
     */
    protected function formatConfirmationMessage(array $data): string
    {
        $message = "*Проверьте данные:*\n\n";

        foreach ($data as $label => $value) {
            if ($value !== null && $value !== '') {
                $message .= "*{$label}:* {$value}\n";
            }
        }

        $message .= "\nВсё верно?";

        return $message;
    }


    /**
     * Обработка подтверждения
     */
    public function handleConfirmation(Nutgram $bot): void
    {
        $callbackQuery = $bot->callbackQuery();

        if ($callbackQuery === null) {
            $bot->sendMessage("Пожалуйста, нажмите одну из кнопок выше.");
            return;
        }

        $data = $callbackQuery->data;
        $bot->answerCallbackQuery();

        $prefix = $this->getCallbackPrefix();

        match ($data) {
            "{$prefix}_confirm:yes" => $this->save($bot),
            "close" => $this->end(),
            default => null,
        };
    }

    /**
     * Удаление сообщения с callback
     */
    protected function deleteCallbackMessage(Nutgram $bot): void
    {
        $message = $bot->callbackQuery()?->message;

        if ($message) {
            try {
                $bot->deleteMessage(
                    chat_id: $message->chat->id,
                    message_id: $message->message_id
                );
            } catch (Exception) {
                // Сообщение недоступно для удаления
            }
        }
    }

    /**
     * Создание inline-кнопки с префиксом
     */
    protected function makeButton(string $text, string $action): InlineKeyboardButton
    {
        return InlineKeyboardButton::make(
            $text,
            callback_data: $this->getCallbackPrefix() . "_{$action}"
        );
    }


    protected function isCallbackAction(string $callbackData, string $action): bool
    {
        return $callbackData === $this->getCallbackPrefix() . "_{$action}";
    }

    /**
     * Показ кнопки "Пропустить" для опциональных полей
     */
    protected function askOptionalText(
        Nutgram $bot,
        string $prompt,
        string $skipAction = 'skip'
    ): void {
        $bot->sendMessage(
            text: $prompt,
            reply_markup: InlineKeyboardMarkup::make()
                ->addRow($this->makeButton("Пропустить", $skipAction))
        );
    }

    /**
     * Обработка успешного сохранения
     */
    protected function onSaveSuccess(Nutgram $bot, string $message): void
    {
        $bot->sendMessage(
            text: "{$message}",
            parse_mode: 'Markdown'
        );
        $this->end();
    }

    /**
     * Обработка ошибки сохранения
     */
    protected function onSaveError(Nutgram $bot, Exception $e): void
    {
        $bot->sendMessage(
            text: "Ошибка: " . $e->getMessage()
        );
        $this->showConfirmation($bot);
    }

    protected function editOrSendMessage(
        Nutgram              $bot,
        string               $text,
        ?InlineKeyboardMarkup $keyboard = null,
        string               $parseMode = 'Markdown'
    ): void
    {
        $message = $bot->callbackQuery()?->message;

        if ($message) {
            $bot->editMessageText(
                $text,
                $message->chat->id,
                $message->message_id,
                reply_markup: $keyboard,
                parse_mode: $parseMode
            );
        } else {
            $bot->sendMessage(
                $text,
                reply_markup: $keyboard,
                parse_mode: $parseMode
            );
        }
    }
}
