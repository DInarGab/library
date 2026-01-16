<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Infrastructure\Bot\Commands;

use Dinargab\LibraryBot\Application\Book\DTO\SuggestBookRequestDTO;
use Dinargab\LibraryBot\Application\Book\UseCase\SuggestBookUseCase;
use Dinargab\LibraryBot\Application\Shared\DTO\UserDTO;
use Dinargab\LibraryBot\Infrastructure\Bot\Service\KeyboardService;
use Exception;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

class SuggestBookConversation extends BaseConversation
{
    public ?string $suggestType = null;
    public ?string $url = null;
    public ?string $title = null;
    public ?string $author = null;
    public ?string $comment = null;

    public function __construct(
        private SuggestBookUseCase $suggestBookUseCase,
        protected KeyboardService $keyboardService,
    ) {
        parent::__construct($this->keyboardService);
    }

    protected function getCallbackPrefix(): string
    {
        return 'suggest_book';
    }

    protected function resetState(): void
    {
        $this->suggestType = null;
        $this->url = null;
        $this->title = null;
        $this->author = null;
        $this->comment = null;
    }

    protected function getConfirmationData(): array
    {
        if ($this->suggestType === 'url') {
            return [
                'Ссылка' => $this->url,
                'Комментарий' => $this->comment,
            ];
        }

        return [
            'Название' => $this->title,
            'Автор' => $this->author,
            'Комментарий' => $this->comment,
        ];
    }

    public function start(Nutgram $bot): void
    {
        $this->resetState();

        $bot->sendMessage(
            text: "📚 *Предложить книгу*\n\nВыберите способ добавления:",
            parse_mode: 'Markdown',
            reply_markup: InlineKeyboardMarkup::make()
                ->addRow($this->makeButton("По ссылке", "type:url"))
                ->addRow($this->makeButton("Ввести вручную", "type:manual"))
                ->addRow(InlineKeyboardButton::make("Отмена", callback_data: "close"))
        );

        $this->next('handleTypeSelection');
    }

    public function handleTypeSelection(Nutgram $bot): void
    {
        $callbackQuery = $bot->callbackQuery();

        if ($callbackQuery === null) {
            $bot->sendMessage("Пожалуйста, нажмите одну из кнопок выше.");
            return;
        }

        $data = $callbackQuery->data;
        $bot->answerCallbackQuery();

        if ($this->isCallbackAction($data, 'type:url')) {
            $this->suggestType = 'url';
            $this->editOrSendMessage(
                bot: $bot,
                text: "*Предложение книги по ссылке*\n\n" .
                "Отправьте ссылку на книгу:",
            );
            $this->next('askUrl');

        } elseif ($this->isCallbackAction($data, 'type:manual')) {
            $this->suggestType = 'manual';
            $this->editOrSendMessage(
                bot: $bot,
                text: "*Предложение книги вручную*\n\nВведите название книги:",
            );
            $this->next('askTitle');

        } elseif ($data === 'close') {
            $this->end();
        }
    }

    public function askUrl(Nutgram $bot): void
    {
        $text = $bot->message()?->text;

        if (empty($text)) {
            $bot->sendMessage("Пожалуйста, отправьте ссылку на книгу:");
            return;
        }

        if (!filter_var($text, FILTER_VALIDATE_URL)) {
            $bot->sendMessage("Это не похоже на ссылку. Пожалуйста, отправьте корректную ссылку:");
            return;
        }

        $this->url = $text;
        $this->askForComment($bot);
    }

    public function askTitle(Nutgram $bot): void
    {
        $text = $bot->message()?->text;

        if (empty($text)) {
            $bot->sendMessage("Пожалуйста, введите название книги:");
            return;
        }

        $this->title = trim($text);
        $bot->sendMessage("Введите автора книги:");
        $this->next('askAuthor');
    }

    public function askAuthor(Nutgram $bot): void
    {
        $text = $bot->message()?->text;

        if (empty($text)) {
            $bot->sendMessage("Пожалуйста, введите автора книги:");
            return;
        }

        $this->author = trim($text);
        $this->askForComment($bot);
    }

    private function askForComment(Nutgram $bot): void
    {
        $this->askOptionalText(
            $bot,
            "Добавить комментарий? (необязательно)\n\nОтправьте комментарий или нажмите \"Пропустить\":",
            'skip_comment'
        );
        $this->next('handleComment');
    }

    public function handleComment(Nutgram $bot): void
    {
        $callbackQuery = $bot->callbackQuery();

        // Кнопка "Пропустить"
        if ($callbackQuery !== null && $this->isCallbackAction($callbackQuery->data, 'skip_comment')) {
            $bot->answerCallbackQuery();
            $this->comment = null;
            $this->showConfirmation($bot);
            return;
        }

        // Текстовый комментарий
        $text = $bot->message()?->text;

        if (!empty($text)) {
            $this->comment = trim($text);
            $this->showConfirmation($bot);
            return;
        }

        $bot->sendMessage("Отправьте комментарий или нажмите кнопку \"Пропустить\".");
    }

    protected function save(Nutgram $bot): void
    {

        try {
            /** @var UserDTO $user */
            $user = $this->bot->get('user');
            $dto = new SuggestBookRequestDTO(
                id: $user->id,
                url: $this->url,
                title: $this->title,
                author: $this->author,
                comment: $this->comment,
            );

            $suggestion = ($this->suggestBookUseCase)($dto);

            $this->onSaveSuccess(
                $bot,
                "*Спасибо за предложение!*\n\n" .
                "Ваша заявка принята и будет рассмотрена.\n" .
                "Номер заявки: #{$suggestion->id}"
            );
        } catch (Exception $e) {
            $this->onSaveError($bot, $e);
        }
    }
}
