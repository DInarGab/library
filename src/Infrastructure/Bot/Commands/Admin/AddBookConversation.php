<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Infrastructure\Bot\Commands\Admin;

use Dinargab\LibraryBot\Application\Book\DTO\AddBookRequestDTO;
use Dinargab\LibraryBot\Application\Book\UseCase\AddBookUseCase;
use Dinargab\LibraryBot\Domain\Book\ValueObject\ISBN;
use Dinargab\LibraryBot\Infrastructure\Bot\Commands\BaseConversation;
use Dinargab\LibraryBot\Infrastructure\Bot\Service\KeyboardService;
use Exception;
use InvalidArgumentException;
use SergiX44\Nutgram\Nutgram;

class AddBookConversation extends BaseConversation
{
    public string $author = '';
    public string $title = '';
    public string $description = '';
    public string $isbn = '';

    public function __construct(
        private AddBookUseCase $addBookUseCase,
        protected KeyboardService $keyboardService,
    ) {
        parent::__construct($this->keyboardService);
    }

    protected function getCallbackPrefix(): string
    {
        return 'add_book';
    }

    protected function resetState(): void
    {
        $this->author = '';
        $this->title = '';
        $this->description = '';
        $this->isbn = '';
    }

    protected function getConfirmationData(): array
    {
        return [
            'Название' => $this->title,
            'Автор' => $this->author,
            'Описание' => $this->description,
            'ISBN' => $this->isbn,
        ];
    }

    public function start(Nutgram $bot): void
    {
        $this->resetState();

        $bot->sendMessage("*Добавление книги*\n\nВведите автора книги:", parse_mode: 'Markdown');
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
        $bot->sendMessage("Введите название книги:");
        $this->next('askTitle');
    }

    public function askTitle(Nutgram $bot): void
    {
        $text = $bot->message()?->text;

        if (empty($text)) {
            $bot->sendMessage("Пожалуйста, введите название книги:");
            return;
        }

        $this->title = trim($text);
        $bot->sendMessage("Введите описание книги:");
        $this->next('askDescription');
    }

    public function askDescription(Nutgram $bot): void
    {
        $text = $bot->message()?->text;

        if (empty($text)) {
            $bot->sendMessage("Пожалуйста, введите описание книги:");
            return;
        }

        $this->description = trim($text);
        $bot->sendMessage("Введите ISBN книги:");
        $this->next('askIsbn');
    }

    public function askIsbn(Nutgram $bot): void
    {
        $text = $bot->message()?->text;

        if (empty($text)) {
            $bot->sendMessage("Пожалуйста, введите ISBN:");
            return;
        }

        try {
            new ISBN($text);
            $this->isbn = $text;
            $this->showConfirmation($bot);
        } catch (InvalidArgumentException $exception) {
            $bot->sendMessage($exception->getMessage());
            $bot->sendMessage("Пожалуйста, введите корректный ISBN:");
        }
    }

    protected function save(Nutgram $bot): void
    {
        try {
            ($this->addBookUseCase)(new AddBookRequestDTO(
                $this->title,
                $this->author,
                1,
                $this->isbn,
                $this->description,
            ));

            $this->onSaveSuccess($bot, "*Книга успешно добавлена!*");
        } catch (Exception $e) {
            $this->onSaveError($bot, $e);
        }
    }
}
