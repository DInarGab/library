<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Infrastructure\Bot\Commands\Admin;

use DateTimeImmutable;
use Dinargab\LibraryBot\Application\Book\DTO\GetBookRequestDTO;
use Dinargab\LibraryBot\Application\Book\UseCase\GetBookUseCase;
use Dinargab\LibraryBot\Application\Lending\DTO\LendingRequestDTO;
use Dinargab\LibraryBot\Application\Lending\UseCase\LendBookUseCase;
use Dinargab\LibraryBot\Application\Shared\DTO\LendingDTO;
use Dinargab\LibraryBot\Application\User\DTO\GetUserInfoRequestDTO;
use Dinargab\LibraryBot\Application\User\DTO\GetUsersListRequestDTO;
use Dinargab\LibraryBot\Application\User\UseCase\GetUserInfoUseCase;
use Dinargab\LibraryBot\Application\User\UseCase\GetUsersListUseCase;
use Dinargab\LibraryBot\Domain\Exception\BookNotFoundException;
use Dinargab\LibraryBot\Infrastructure\Bot\Commands\ListBooksCommand;
use Dinargab\LibraryBot\Infrastructure\Bot\Service\PaginationKeyboardService;
use DomainException;
use SergiX44\Nutgram\Conversations\Conversation;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

class LendBookCommand extends Conversation
{
    public const CALLBACK_PREFIX = 'lend_book';
    public const SELECT_USER_PREFIX = 'lend_select_user';
    public const SELECT_PERIOD_PREFIX = 'lend_select_period';
    public const USERS_PAGE_PREFIX = 'lend_users_page';

    private const LENDING_PERIODS = [
        7 => '1 неделя',
        14 => '2 недели',
        21 => '3 недели',
        30 => '1 месяц',
    ];

    private const BACK_TO_USERS_CALLBACK = 'lend_back_to_users';
    private const BACK_TO_PERIOD_CALLBACK = 'lend_back_to_period';
    private const CONFIRM_CALLBACK = 'lend_confirm';

    private const USERS_PER_PAGE = 5;

    public ?int $bookId = null;
    public ?int $selectedUserId = null;
    public ?int $selectedDays = null;

    public function __construct(
        private readonly GetBookUseCase            $getBookUseCase,
        private readonly GetUsersListUseCase       $getUsersListUseCase,
        private readonly LendBookUseCase           $issueBookUseCase,
        private readonly PaginationKeyboardService $paginationKeyboardService,
        private readonly GetUserInfoUseCase        $getUserInfoUseCase,
    )
    {
    }

    public function start(Nutgram $bot, string $bookId): void
    {

        $this->bookId = (int)$bookId;
        try {
            $book = ($this->getBookUseCase)(new GetBookRequestDTO($this->bookId));
        } catch (BookNotFoundException $exception) {
            $bot->sendMessage($exception->getMessage());
            $this->end();
            return;
        }
        if ($book->availableCopies <= 0) {
            $bot->sendMessage('Нет доступных для выдачи копий');
            $this->end();
            return;
        }

        $this->showUsersSelection($bot, $book);
    }

    private function showUsersSelection(Nutgram $bot, object $book): void
    {
        $text = $this->formatBookInfo($book) . "\n\n *Выберите пользователя для выдачи:*";
        $this->showUsersPage($bot, 1, $text);
        $this->next('handleUserSelection');
    }

    public function handleUserSelection(Nutgram $bot): void
    {
        $callbackData = $bot->callbackQuery()?->data;

        if (!$callbackData) {
            return;
        }

        match (true) {
            str_starts_with($callbackData, self::USERS_PAGE_PREFIX . ':') => $this->handleUsersPagination($bot, $callbackData),
            str_starts_with($callbackData, self::SELECT_USER_PREFIX . ':') => $this->handleUserChoice($bot, $callbackData),
            $callbackData === 'cancel' => $this->end(),
            default => null
        };

        $bot->answerCallbackQuery();
    }

    private function handleUsersPagination(Nutgram $bot, string $callbackData): void
    {
        $page = (int)str_replace(self::USERS_PAGE_PREFIX . ':', '', $callbackData);
        $this->showUsersPage($bot, $page);
    }

    private function handleUserChoice(Nutgram $bot, string $callbackData): void
    {
        $this->selectedUserId = (int)str_replace(self::SELECT_USER_PREFIX . ':', '', $callbackData);
        $this->showPeriodSelection($bot);
    }

    private function showUsersPage(Nutgram $bot, int $page, ?string $headerText = null): void
    {
        $usersResponse = ($this->getUsersListUseCase)(
            new GetUsersListRequestDTO(page: $page, limit: self::USERS_PER_PAGE)
        );

        if (empty($usersResponse->users)) {
            $bot->sendMessage(
                "Нет пользователей для выдачи"
            );
            $this->end();
            return;
        }

        $keyboard = $this->createUsersKeyboard($usersResponse, $page);
        $text = $headerText ?? $this->getUsersListText();

        $this->editOrSendMessage($bot, $text, $keyboard);
    }

    private function createUsersKeyboard(object $usersResponse, int $currentPage): InlineKeyboardMarkup
    {
        $keyboard = InlineKeyboardMarkup::make();

        foreach ($usersResponse->users as $user) {
            $keyboard->addRow($this->createUserButton($user));
        }

        if ($usersResponse->totalPages > 1) {
            $keyboard->addRow(
                ...$this->paginationKeyboardService->createPaginationRow(
                $currentPage,
                $usersResponse->totalPages,
                self::USERS_PAGE_PREFIX
            ));
        }

        $keyboard->addRow(
            InlineKeyboardButton::make('Отмена', callback_data: 'cancel')
        );

        return $keyboard;
    }

    private function createUserButton(object $user): InlineKeyboardButton
    {
        $buttonText = $user->displayName;
        if ($user->username) {
            $buttonText .= " (@{$user->username})";
        }

        return InlineKeyboardButton::make(
            $buttonText,
            callback_data: self::SELECT_USER_PREFIX . ":{$user->id}"
        );
    }


    private function showPeriodSelection(Nutgram $bot): void
    {
        $keyboard = $this->createPeriodSelectionKeyboard();
        $text = "*Выдача книги*\n\n Пользователь выбран\n\n *Выберите срок выдачи:*";

        $this->editOrSendMessage($bot, $text, $keyboard);
        $this->next('handlePeriodSelection');
    }

    private function createPeriodSelectionKeyboard(): InlineKeyboardMarkup
    {
        $keyboard = InlineKeyboardMarkup::make();

        foreach (array_chunk($this->createPeriodButtons(), 2) as $chunk) {
            $keyboard->addRow(...$chunk);
        }

        $keyboard->addRow(
            InlineKeyboardButton::make('⬅️ Назад', callback_data: self::BACK_TO_USERS_CALLBACK),
            InlineKeyboardButton::make('Отмена', callback_data: 'cancel')
        );

        return $keyboard;
    }

    private function createPeriodButtons(): array
    {
        $buttons = [];
        foreach (self::LENDING_PERIODS as $days => $label) {
            $buttons[] = InlineKeyboardButton::make(
                $label,
                callback_data: self::SELECT_PERIOD_PREFIX . ":{$days}"
            );
        }
        return $buttons;
    }

    public function handlePeriodSelection(Nutgram $bot): void
    {
        $callbackData = $bot->callbackQuery()?->data;

        if (!$callbackData) {
            return;
        }

        match (true) {
            str_starts_with($callbackData, self::SELECT_PERIOD_PREFIX . ':') => $this->handlePeriodChoice($bot, $callbackData),
            $callbackData === self::BACK_TO_USERS_CALLBACK => $this->showUsersPage($bot, 1),
            $callbackData === 'cancel' => $this->end(),
            default => null
        };

        $bot->answerCallbackQuery();
    }

    private function handlePeriodChoice(Nutgram $bot, string $callbackData): void
    {
        $this->selectedDays = (int)str_replace(self::SELECT_PERIOD_PREFIX . ':', '', $callbackData);
        $this->confirmLending($bot);
    }

    private function confirmLending(Nutgram $bot): void
    {
        $book = ($this->getBookUseCase)(new GetBookRequestDTO($this->bookId));
        $user = ($this->getUserInfoUseCase)(new GetUserInfoRequestDTO($this->selectedUserId));

        if (!$user) {
            $bot->sendMessage(
                "Пользователь не найден"
            );
            $this->end();
            return;
        }

        $dueDate = (new DateTimeImmutable())->modify("+{$this->selectedDays} days");

        $keyboard = InlineKeyboardMarkup::make()
            ->addRow(
                InlineKeyboardButton::make('✅ Подтвердить', callback_data: self::CONFIRM_CALLBACK)
            )
            ->addRow(
                InlineKeyboardButton::make('⬅️ Назад', callback_data: self::BACK_TO_PERIOD_CALLBACK),
                InlineKeyboardButton::make('Отмена', callback_data: 'cancel'),
            );

        $text = $this->formatConfirmationText($book, $user, $dueDate);

        $this->editOrSendMessage($bot, $text, $keyboard);
        $this->next('handleConfirmation');
    }

    private function formatConfirmationText(object $book, object $user, DateTimeImmutable $dueDate): string
    {
        $text = "*Подтверждение выдачи*\n\n"
            . "*Книга:* $book->title\n"
            . "*Автор:* $book->author\n\n"
            . "*Пользователь:* $user->displayName\n";

        if ($user->username) {
            $text .= "*Username:* @$user->username\n";
        }

        $text .= "\n*Срок:* {$this->selectedDays} дней\n"
            . "*Вернуть до:* {$dueDate->format('d.m.Y')}\n\n"
            . "*Подтвердить выдачу?*";

        return $text;
    }

    public function handleConfirmation(Nutgram $bot): void
    {
        $callbackData = $bot->callbackQuery()?->data;

        if (!$callbackData) {
            return;
        }

        match ($callbackData) {
            self::CONFIRM_CALLBACK => $this->processLending($bot),
            self::BACK_TO_PERIOD_CALLBACK => $this->showPeriodSelection($bot),
            default => null
        };

        $bot->answerCallbackQuery();
    }

    private function processLending(Nutgram $bot): void
    {
        try {
            $lending = ($this->issueBookUseCase)(

                new LendingRequestDTO(
                    bookId: $this->bookId,
                    userId: $this->selectedUserId,
                    daysToReturn: $this->selectedDays
                )
            );

            $this->showSuccessMessage($bot, $lending);
        } catch (DomainException $e) {
            $this->showErrorMessage($bot, $e);
        }

        $this->end();
    }

    private function showSuccessMessage(Nutgram $bot, LendingDTO $lending): void
    {
        $text = "*Книга успешно выдана!*\n\n"
            . "*Книга:* {$lending->bookTitle}\n"
            . "*Пользователь:* {$lending->userName}\n"
            . "*Дата выдачи:* {$lending->issuedAt}\n"
            . "*Вернуть до:* {$lending->dueDate}\n";

        $keyboard = InlineKeyboardMarkup::make()
            ->addRow(
                InlineKeyboardButton::make('К списку книг', callback_data: ListBooksCommand::PAGINATION_PREFIX . ':1')
            );

        $this->editOrSendMessage($bot, $text, $keyboard);
    }

    private function showErrorMessage(Nutgram $bot, DomainException $e): void
    {
        $text = "*Ошибка:* {$e->getMessage()}";

        $keyboard = InlineKeyboardMarkup::make()
            ->addRow(
                InlineKeyboardButton::make('Попробовать снова', callback_data: self::CALLBACK_PREFIX . ":{$this->bookId}")
            )
            ->addRow(
                InlineKeyboardButton::make('Закрыть', callback_data: 'close')
            );

        $this->editOrSendMessage($bot, $text, $keyboard);
    }

    private function formatBookInfo(object $book): string
    {
        return "*Книга*\n\n"
            . "*\"$book->title\"*\n"
            . "$book->author\n"
            . "Доступно копий: $book->availableCopies";
    }

    private function getUsersListText(): string
    {
        $book = ($this->getBookUseCase)(new GetBookRequestDTO($this->bookId));
        return $this->formatBookInfo($book) . "\n\n👤 *Выберите пользователя для выдачи:*";
    }

    public function editOrSendMessage(
        Nutgram              $bot,
        string               $text,
        InlineKeyboardMarkup $keyboard,
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
