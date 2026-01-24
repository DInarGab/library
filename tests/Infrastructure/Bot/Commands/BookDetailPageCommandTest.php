<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Tests\Infrastructure\Bot\Commands;

use Dinargab\LibraryBot\Application\Shared\DTO\UserDTO;
use Dinargab\LibraryBot\Infrastructure\Bot\Commands\Admin\DeleteBookCommand;
use Dinargab\LibraryBot\Infrastructure\Bot\Commands\BookDetailPageCommand;
use Dinargab\LibraryBot\Infrastructure\Bot\Commands\ListBooksCommand;
use Dinargab\LibraryBot\Tests\Factory\Book\Entity\BookFactory;
use GuzzleHttp\Psr7\Request;
use SergiX44\Nutgram\Nutgram;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class BookDetailPageCommandTest extends KernelTestCase
{
    use ResetDatabase, Factories;

    private BookDetailPageCommand $commandHandler;
    private Nutgram $bot;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->commandHandler = self::getContainer()->get(BookDetailPageCommand::class);

        // Initialize Fake Bot
        $this->bot = Nutgram::fake();
        $this->bootBotLogic($this->bot);
    }

    private function bootBotLogic(Nutgram $bot): void
    {
        // Register the specific command handler logic
        $bot->onCallbackQueryData(BookDetailPageCommand::COMMAND_PREFIX . ':{bookId}', $this->commandHandler);
    }

    public function testBookNotFound(): void
    {
        // Попробуем получить книжку с несуществующим ID
        $this->bot->hearCallbackQueryData(BookDetailPageCommand::COMMAND_PREFIX . ':99999')
                  ->reply()
                  ->assertReply('sendMessage', [
                      'text' => 'Книга не найдена',
                  ]);
    }

    public function testBookDetailsAsRegularUser(): void
    {
        $book = BookFactory::new()
                           ->withCopies(1)
                           ->create([
                               'title'       => 'Книга',
                               'author'      => 'Автор книги',
                               'isbn'        => '9780306406157',
                               'description' => 'Описание',
                           ]);

        $userDto = new UserDTO(
            id: 1,
            displayName: 'RegularUser',
            username: 'User',
            telegramId: '213213432',
            isAdmin: false
        );
        $this->bot->set('user', $userDto);

        $this->bot->hearCallbackQueryData(BookDetailPageCommand::COMMAND_PREFIX . ':' . $book->getId())
                  ->reply()
                  ->assertRaw(function (Request $request) use ($book) {
                      $content = json_decode((string)$request->getBody(), true, flags: JSON_THROW_ON_ERROR);

                      $text = $content['text'];
                      $this->assertStringContainsString($book->getTitle(), $text);
                      $this->assertStringContainsString($book->getAuthor(), $text);
                      $this->assertStringContainsString((string)$book->getIsbn(), $text);
                      $this->assertStringContainsString($book->getDescription(), $text);
                      $this->assertStringContainsString('*Доступно копий:* 1', $text);

                      // Проверяем, что есть кнопка назад
                      $keyboard = $content['reply_markup']['inline_keyboard'];

                      // У обычного пользователя 1 ряд кнопок
                      $this->assertCount(1, $keyboard);

                      $backButton = $keyboard[0][0];
                      $this->assertStringContainsString('Назад', $backButton['text']);
                      $this->assertEquals(ListBooksCommand::COMMAND_PREFIX . ':1', $backButton['callback_data']);

                      return true;
                  }, message: 'editMessageText');
    }

    public function testBookDetailsAsAdminUser(): void
    {
        $book = BookFactory::new()
                           ->withCopies(2)
                           ->create([
                               'title' => 'Админская книга',
                               'isbn'  => null,
                           ]);

        $userDto = new UserDTO(
            id: 2,
            displayName: 'RegularUser',
            username: 'User',
            telegramId: '213213433',
            isAdmin: true
        );
        $this->bot->set('user', $userDto);

        $this->bot->hearCallbackQueryData(BookDetailPageCommand::COMMAND_PREFIX . ':' . $book->getId())
                  ->reply()
                  ->assertRaw(function (Request $request) use ($book) {
                      $content  = json_decode((string)$request->getBody(), true, flags: JSON_THROW_ON_ERROR);
                      $keyboard = $content['reply_markup']['inline_keyboard'];

                      $buttons   = array_merge(...$keyboard);
                      $callbacks = array_column($buttons, 'callback_data');

                      $this->assertContains("lend_book:{$book->getId()}", $callbacks);

                      $this->assertContains(DeleteBookCommand::COMMAND_PREFIX . ":{$book->getId()}", $callbacks);

                      $this->assertContains(ListBooksCommand::COMMAND_PREFIX . ':1', $callbacks);

                      return true;
                  });
    }
}
