<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Infrastructure\Bot\Commands\Admin;

use Dinargab\LibraryBot\Application\Book\DTO\AddBookRequestDTO;
use Dinargab\LibraryBot\Application\Book\UseCase\AddBookUseCase;
use Dinargab\LibraryBot\Domain\Book\ValueObject\ISBN;
use Exception;
use InvalidArgumentException;
use SergiX44\Nutgram\Conversations\Conversation;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

class AddBookCommand extends Conversation
{
    public string $author = '';
    public string $title = '';
    public string $description = '';
    public string $isbn = '';

    public function __construct(
        private AddBookUseCase $addBookUseCase,
    )
    {
    }

    public function start(Nutgram $bot): void
    {
        $this->author = '';
        $this->title = '';
        $this->description = '';
        $this->isbn = '';

        $bot->sendMessage("Введите автора книги:");
        $this->next('askAuthor');
    }

    public function askAuthor(Nutgram $bot): void
    {
        $text = $bot->message()?->text;

        if (empty($text)) {
            $bot->sendMessage("Пожалуйста, введите текст. Введите автора книги:");
            return;
        }

        $this->author = $text;
        $bot->sendMessage("Введите название книги:");
        $this->next('askTitle');
    }

    public function askTitle(Nutgram $bot): void
    {
        $text = $bot->message()?->text;

        if (empty($text)) {
            $bot->sendMessage("Пожалуйста, введите текст. Введите название книги:");
            return;
        }

        $this->title = $text;
        $bot->sendMessage("Введите описание книги:");
        $this->next('askDescription');
    }

    public function askDescription(Nutgram $bot): void
    {
        $text = $bot->message()?->text;

        if (empty($text)) {
            $bot->sendMessage("Пожалуйста, введите текст. Введите описание книги:");
            return;
        }

        $this->description = $text;
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
            // Валидация ISBN
            new ISBN($text);
            $this->isbn = $text;

            $this->showRecap($bot);
            $this->next('handleConfirmation');

        } catch (InvalidArgumentException $exception) {
            $bot->sendMessage($exception->getMessage());
            $bot->sendMessage("Пожалуйста, введите корректный ISBN:");
        }
    }

    private function showRecap(Nutgram $bot): void
    {
        $message = "Вы хотите добавить книгу:\n
                Название: {$this->title}\n
                Автор: {$this->author}\n
                Описание: {$this->description}\n
                ISBN: {$this->isbn}\n";

        $bot->sendMessage(
            text: $message,
            reply_markup: InlineKeyboardMarkup::make()->addRow(
                InlineKeyboardButton::make("Да, добавить", callback_data: "confirm_yes"),
                InlineKeyboardButton::make("Начать заново", callback_data: "confirm_no"),
            )
        );
    }

    public function handleConfirmation(Nutgram $bot): void
    {
        $callbackQuery = $bot->callbackQuery();

        if ($callbackQuery === null) {
            $bot->sendMessage("Пожалуйста, нажмите одну из кнопок выше.");
            return;
        }

        $data = $callbackQuery->data;
        $bot->answerCallbackQuery();

        match ($data) {
            'confirm_yes' => $this->saveBook($bot),
            'confirm_no' => $this->start($bot),
            default => null,
        };
    }

    private function saveBook(Nutgram $bot): void
    {
        try {
            ($this->addBookUseCase)(new AddBookRequestDTO(
                $this->title,
                $this->author,
                1,
                $this->isbn,
                $this->description,
            ));

            $bot->sendMessage("Книга успешно добавлена!");
            $this->end();

        } catch (Exception $e) {
            $bot->sendMessage("Произошла ошибка при добавлении книги: " . $e->getMessage());
            $this->showRecap($bot);
            $this->next('handleConfirmation');
        }
    }
}
