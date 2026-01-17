<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Infrastructure\Bot\Commands\Admin;

use Dinargab\LibraryBot\Application\Book\DTO\GetBookRequestDTO;
use Dinargab\LibraryBot\Application\Book\UseCase\GetBookUseCase;
use Dinargab\LibraryBot\Application\Lending\DTO\LendingRequestDTO;
use Dinargab\LibraryBot\Application\Lending\UseCase\LendBookUseCase;
use Dinargab\LibraryBot\Application\Shared\DTO\BookDTO;
use Dinargab\LibraryBot\Application\Shared\DTO\LendingDTO;
use Dinargab\LibraryBot\Application\Shared\DTO\UserDTO;
use Dinargab\LibraryBot\Application\User\DTO\GetUserInfoRequestDTO;
use Dinargab\LibraryBot\Application\User\DTO\GetUsersListRequestDTO;
use Dinargab\LibraryBot\Application\User\UseCase\GetUserInfoUseCase;
use Dinargab\LibraryBot\Application\User\UseCase\GetUsersListUseCase;
use Dinargab\LibraryBot\Domain\Exception\BookNotFoundException;
use Dinargab\LibraryBot\Infrastructure\Bot\Commands\BaseConversation;
use Dinargab\LibraryBot\Infrastructure\Bot\Commands\ListBooksCommand;
use Dinargab\LibraryBot\Infrastructure\Bot\Service\KeyboardService;
use DomainException;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

class LendBookConversation extends BaseConversation
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

    private const USERS_PER_PAGE = 5;

    public ?int $bookId = null;
    public ?int $selectedUserId = null;
    public ?int $selectedDays = null;
    public ?BookDTO $bookDTO = null;

    public function __construct(
        private readonly GetBookUseCase      $getBookUseCase,
        private readonly GetUsersListUseCase $getUsersListUseCase,
        private readonly LendBookUseCase     $issueBookUseCase,
        private readonly GetUserInfoUseCase  $getUserInfoUseCase,
        protected KeyboardService            $keyboardService,
    )
    {
        parent::__construct($this->keyboardService);
    }

    public function start(Nutgram $bot, string $bookId): void
    {

        $this->bookId = (int)$bookId;
        try {
            $book = $this->getBook();
        } catch (BookNotFoundException $exception) {
            $this->editOrSendMessage($bot, $exception->getMessage());
            $this->end();
            return;
        }
        if ($book->availableCopies <= 0) {
            $this->editOrSendMessage($bot, 'Нет доступных для выдачи копий');
            $this->end();
            return;
        }

        $this->showUsersPage($bot, 1);
        $this->next('handleUserSelection');
    }

    /**
     * Выбор пользвователя начало
     **/
    public function handleUserSelection(Nutgram $bot): void
    {
        $callbackData = $bot->callbackQuery()?->data;

        if (!$callbackData) {
            return;
        }
        $bot->answerCallbackQuery();

        match (true) {
            str_starts_with($callbackData, self::USERS_PAGE_PREFIX . ':')
            => $this->showUsersPage($bot, (int)str_replace(self::USERS_PAGE_PREFIX . ':', '', $callbackData)),
            str_starts_with($callbackData, self::SELECT_USER_PREFIX . ':')
            => $this->handleUserSelected($bot, $callbackData),
            $callbackData === 'cancel'
            => $this->end(),
            default => null,
        };

    }

    private function handleUserSelected(Nutgram $bot, string $callbackData): void
    {
        $this->selectedUserId = (int)str_replace(self::SELECT_USER_PREFIX . ':', '', $callbackData);
        $this->showPeriodSelection($bot);
    }


    private function showUsersPage(Nutgram $bot, int $page): void
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

        $keyboard = $this->keyboardService->createListWithPagination(
            $usersResponse->users,
            $page,
            $usersResponse->totalPages,
            fn(UserDTO $user) => $this->createUserButton($user),
            self::USERS_PAGE_PREFIX,
        );

        $text = $this->formatBookInfo();

        $this->editOrSendMessage($bot, $text, $keyboard);
    }

    private function createUserButton(UserDTO $user): InlineKeyboardButton
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

    /**
     * Выбор периода выдачи начало
     **/
    private function showPeriodSelection(Nutgram $bot): void
    {
        $keyboard = $this->keyboardService->createNavigationKeyboard(
            backCallback: self::BACK_TO_USERS_CALLBACK,
            closeCallback: 'close',
            additionalButtons: $this->createPeriodButtons()
        );
        $text = "*Выдача книги*\n\n Пользователь выбран\n\n *Выберите срок выдачи:*";

        $this->editOrSendMessage($bot, $text, $keyboard);
        $this->next('handlePeriodSelection');
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
        $bot->answerCallbackQuery();

        if (str_starts_with($callbackData, self::SELECT_PERIOD_PREFIX . ':')) {
            $this->selectedDays = (int)str_replace(self::SELECT_PERIOD_PREFIX . ':', '', $callbackData);
            $this->showConfirmation($bot);
        }

        if ($callbackData === self::BACK_TO_USERS_CALLBACK) {
            $this->showUsersPage($bot, 1);
        }

        if ($callbackData === 'cancel') {
            $this->end();
        }

    }

    protected function getConfirmationData(): array
    {
        $book = $this->getBook();
        $user = ($this->getUserInfoUseCase)(new GetUserInfoRequestDTO($this->selectedUserId));

        return [
            'Книга' => $book->title,
            'Автор' => $book->author,
            'Пользователь' => $user->displayName,
            'Username' => $user->username ? "@{$user->username}" : null,
            'Срок' => self::LENDING_PERIODS[$this->selectedDays],
        ];
    }


    protected function save(Nutgram $bot): void
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
                InlineKeyboardButton::make('К списку книг', callback_data: ListBooksCommand::COMMAND_PREFIX . ':1')
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

    private function formatBookInfo(): string
    {
        $book = $this->bookDTO;
        return "*Книга*\n\n"
            . "*\"$book->title\"*\n"
            . "$book->author\n"
            . "Доступно копий: $book->availableCopies";
    }

    protected function getCallbackPrefix(): string
    {
        return self::CALLBACK_PREFIX;
    }

    protected function resetState(): void
    {
        $this->bookId = null;
        $this->selectedUserId = null;
        $this->selectedDays = null;
        $this->bookDTO = null;
    }

    private function getBook(): ?BookDTO
    {
        if (is_null($this->bookDTO) && $this->bookId !== null) {
            $this->bookDTO = ($this->getBookUseCase)(new GetBookRequestDTO($this->bookId));
        }
        return $this->bookDTO;
    }
}
