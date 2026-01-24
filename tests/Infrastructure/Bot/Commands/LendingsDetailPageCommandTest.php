<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Tests\Infrastructure\Bot\Commands;

use Dinargab\LibraryBot\Application\Shared\DTO\UserDTO;
use Dinargab\LibraryBot\Domain\Lending\ValueObject\LendingStatus;
use Dinargab\LibraryBot\Infrastructure\Bot\Commands\Admin\ReturnBookCommand;
use Dinargab\LibraryBot\Infrastructure\Bot\Commands\LendingsDetailPageCommand;
use Dinargab\LibraryBot\Infrastructure\Bot\Commands\ListLendingsCommand;
use Dinargab\LibraryBot\Tests\Factory\Book\Entity\BookCopyFactory;
use Dinargab\LibraryBot\Tests\Factory\Book\Entity\BookFactory;
use Dinargab\LibraryBot\Tests\Factory\Lending\Entity\LendingFactory;
use Dinargab\LibraryBot\Tests\Factory\User\Entity\UserFactory;
use GuzzleHttp\Psr7\Request;
use SergiX44\Nutgram\Nutgram;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class LendingsDetailPageCommandTest extends KernelTestCase
{
    use ResetDatabase, Factories;

    private LendingsDetailPageCommand $commandHandler;
    private Nutgram $bot;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->commandHandler = self::getContainer()->get(LendingsDetailPageCommand::class);

        $this->bot = Nutgram::fake();
        $this->bootBotLogic($this->bot);
    }

    private function bootBotLogic(Nutgram $bot): void
    {
        $bot->onCallbackQueryData(LendingsDetailPageCommand::COMMAND_PREFIX . ':{lendingId}', $this->commandHandler);
    }

    public function testLendingNotFound(): void
    {
        // Имитируем админа, чтобы убедиться, что ошибка выбрасывается до проверок прав
        $this->mockUser(isAdmin: true);

        $this->bot->hearCallbackQueryData(LendingsDetailPageCommand::COMMAND_PREFIX . ':99999')
                  ->reply()
                  ->assertReply('sendMessage', [
                      'text' => 'Lending not found',
                  ]);
    }

    public function testLendingDetailsAsRegularUser(): void
    {
        $borrower = UserFactory::new()->create([
            'firstName' => 'John',
            'lastName' => 'Doe',
        ]);

        $book = BookFactory::new()->create([
            'title' => 'The Great Gatsby',
            'author' => 'F. Scott Fitzgerald',
        ]);

        $bookCopy = BookCopyFactory::new()->create([
            'book' => $book,
        ]);

        $lending = LendingFactory::new()->create([
            'user' => $borrower,
            'bookCopy' => $bookCopy,
        ]);

        // 2. Устанавливаем текущего пользователя бота (наблюдателя) как обычного юзера
        $this->mockUser(isAdmin: false);

        // 3. Выполняем действие
        $this->bot->hearCallbackQueryData(LendingsDetailPageCommand::COMMAND_PREFIX . ':' . $lending->getId())
                  ->reply()
                  ->assertRaw(function (Request $request) use ($book, $lending) {
                      $content = json_decode((string)$request->getBody(), true, flags: JSON_THROW_ON_ERROR);
                      $text = $content['text'];
                      $keyboard = $content['reply_markup']['inline_keyboard'];

                      // Проверки текста
                      $this->assertStringContainsString($book->getTitle(), $text);
                      $this->assertStringContainsString($book->getAuthor(), $text);

                      // Обычный пользователь НЕ должен видеть, кому выдана книга
                      $this->assertStringNotContainsString('Выдана пользователю:', $text);

                      $buttons = array_merge(...$keyboard);
                      $callbacks = array_column($buttons, 'callback_data');

                      // Кнопка "Назад" должна быть
                      $this->assertContains(ListLendingsCommand::COMMAND_PREFIX . ":1", $callbacks);

                      // Кнопки "Вернуть книгу" быть НЕ должно (юзер не админ)
                      $returnCallback = ReturnBookCommand::RETURN_BOOK_PREFIX . ":{$lending->getId()}";
                      $this->assertNotContains($returnCallback, $callbacks);

                      return true;
                  }, message: 'editMessageText');
    }

    public function testLendingDetailsAsAdminUserWithActiveLending(): void
    {
        $borrower = UserFactory::new()->create(['firstName' => 'Alice', 'lastName' => 'Smith']);
        $book = BookFactory::new()->create(['title' => 'Clean Code']);
        $bookCopy = BookCopyFactory::new()->create(['book' => $book]);

        $lending = LendingFactory::new()->create([
            'user' => $borrower,
            'bookCopy' => $bookCopy,
        ]);

        $this->mockUser(isAdmin: true);

        $this->bot->hearCallbackQueryData(LendingsDetailPageCommand::COMMAND_PREFIX . ':' . $lending->getId())
                  ->reply()
                  ->assertRaw(function (Request $request) use ($lending, $borrower) {
                      $content = json_decode((string)$request->getBody(), true, flags: JSON_THROW_ON_ERROR);
                      $text = $content['text'];
                      $keyboard = $content['reply_markup']['inline_keyboard'];

                      // Админ должен видеть имя пользователя
                      $this->assertStringContainsString('Выдана пользователю:', $text);
                      $this->assertStringContainsString($borrower->getDisplayName(), $text);

                      // Проверки клавиатуры
                      $buttons = array_merge(...$keyboard);
                      $callbacks = array_column($buttons, 'callback_data');

                      // Кнопка "Вернуть книгу" ДОЛЖНА быть
                      $this->assertContains(ReturnBookCommand::RETURN_BOOK_PREFIX . ":{$lending->getId()}", $callbacks);

                      return true;
                  });
    }

    public function testLendingDetailsAsAdminUserWithReturnedLending(): void
    {
        $lending = LendingFactory::new()->create([
            'status' => LendingStatus::RETURNED,
            'user' => UserFactory::new()->create(),
            'bookCopy' => BookCopyFactory::new()->create()
        ]);

        $this->mockUser(isAdmin: true);

        $this->bot->hearCallbackQueryData(LendingsDetailPageCommand::COMMAND_PREFIX . ':' . $lending->getId())
                  ->reply()
                  ->assertRaw(function (Request $request) use ($lending) {
                      $content = json_decode((string)$request->getBody(), true, flags: JSON_THROW_ON_ERROR);
                      $keyboard = $content['reply_markup']['inline_keyboard'];

                      $buttons = array_merge(...$keyboard);
                      $callbacks = array_column($buttons, 'callback_data');

                      // Кнопки "Вернуть книгу" быть НЕ должно, так как статус RETURNED
                      $this->assertNotContains(ReturnBookCommand::RETURN_BOOK_PREFIX . ":{$lending->getId()}", $callbacks);

                      // Кнопка "Назад" должна остаться
                      $this->assertContains(ListLendingsCommand::COMMAND_PREFIX . ":1", $callbacks);

                      return true;
                  });
    }

    private function mockUser(bool $isAdmin): void
    {
        $userDto = new UserDTO(
            id: 123,
            displayName: 'Test User',
            username: 'testuser',
            telegramId: '123456789',
            isAdmin: $isAdmin
        );
        $this->bot->set('user', $userDto);
    }
}
