<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Infrastructure\Bot\Commands\Admin;

use Dinargab\LibraryBot\Application\Book\DTO\GetSuggestionRequestDTO;
use Dinargab\LibraryBot\Application\Book\DTO\SuggestionProcessingRequestDTO;
use Dinargab\LibraryBot\Application\Book\UseCase\GetSuggestionUseCase;
use Dinargab\LibraryBot\Application\Book\UseCase\SuggestionProcessingUseCase;
use Dinargab\LibraryBot\Domain\Book\ValueObject\BookSuggestionStatus;
use Dinargab\LibraryBot\Domain\Exception\BookSuggestionNotFoundException;
use Dinargab\LibraryBot\Infrastructure\Bot\Commands\BaseConversation;
use Dinargab\LibraryBot\Infrastructure\Bot\Service\KeyboardService;
use Exception;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

class SuggestionProcessingCommand extends BaseConversation
{

    public const SKIP_COMMENT_CALLBACK = 'skip_comment';

    public const PROCESS_SUGGESTION_CALLBACK = 'process_suggestion';
    public ?string $adminComment = null;
    public ?int $suggestionId = null;
    public ?BookSuggestionStatus $status;

    public function __construct(
        private SuggestionProcessingUseCase $useCase,
        private GetSuggestionUseCase $getSuggestionUseCase,
        protected KeyboardService $keyboardService,
    )
    {
        parent::__construct($this->keyboardService);
    }

    public function start(Nutgram $bot, string $suggestionId, string $status)
    {
        $this->suggestionId = (int)$suggestionId;
        $this->status = BookSuggestionStatus::from($status);
        try {
            ($this->getSuggestionUseCase)(new GetSuggestionRequestDTO($this->suggestionId));

        } catch (BookSuggestionNotFoundException $exception) {
            $this->editOrSendMessage($bot, 'Предложение не найдено');
            $this->end();
        }

        $this->askForComment($bot);
    }

    public function handleComment(Nutgram $bot): void
    {
        $callbackQuery = $bot->callbackQuery();
        if ($callbackQuery !== null) {
            $this->isCallbackAction($callbackQuery->data, self::SKIP_COMMENT_CALLBACK);
        }
        if ($callbackQuery !== null && $this->isCallbackAction($callbackQuery->data, self::SKIP_COMMENT_CALLBACK)) {
            $bot->answerCallbackQuery();
            $this->adminComment = null;
        }

        $text = $bot->message()?->text;
        if (!empty($text)) {
            $this->adminComment = trim($text);
        }
        $this->save($bot);
    }

    protected function askForComment(Nutgram $bot): void
    {
        $this->editOrSendMessage(
            $bot,
            "Добавить комментарий? (необязательно)\n\nОтправьте комментарий или нажмите \"Пропустить\":",
            InlineKeyboardMarkup::make()->addRow(
                $this->makeButton("Пропустить", self::SKIP_COMMENT_CALLBACK)
            )
        );
        $this->next('handleComment');
    }

    protected function getCallbackPrefix(): string
    {
        return self::PROCESS_SUGGESTION_CALLBACK;
    }

    protected function resetState(): void
    {
        $this->adminComment = null;
    }

    protected function getConfirmationData(): array
    {
        $responseArray = [];
        if ($this->adminComment !== null) {
            $responseArray['*Комментарий администратора*'] = $this->adminComment;
        }
        match ($this->status) {
            BookSuggestionStatus::APPROVED => $responseArray["*Результат*"] = "Предложение было одобрено, уведомление отправлено пользователю",
            BookSuggestionStatus::REJECTED => $responseArray["*Результат*"] = "Предложение было отклонено, уведомление отправлено пользователю",
            default => throw new Exception('Statuses can only be set to Approved or Rejected state.'),
        };
        return $responseArray;
    }

    protected function save(Nutgram $bot): void
    {
        ($this->useCase)(
            new SuggestionProcessingRequestDTO(
                $this->suggestionId,
                $this->adminComment,
                $this->status
            )
        );
        $this->onSaveSuccess(
            $bot,
            "Предложение обработано. Пользователь будет уведомлен о результате"
        );
    }
}
