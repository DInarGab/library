<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Tests\Infrastructure\Bot\Commands\Admin;

use Dinargab\LibraryBot\Application\Shared\DTO\UserDTO;
use Dinargab\LibraryBot\Domain\Book\Entity\Book;
use Dinargab\LibraryBot\Domain\User\ValueObject\UserRole;
use Dinargab\LibraryBot\Infrastructure\Bot\Commands\Admin\AddBookConversation;
use Dinargab\LibraryBot\Infrastructure\Bot\Middlewares\AdminMiddleware;
use Dinargab\LibraryBot\Infrastructure\Bot\Middlewares\UserAuthMiddleware;
use Dinargab\LibraryBot\Tests\Factory\Book\Entity\BookFactory;
use Dinargab\LibraryBot\Tests\Factory\User\Entity\UserFactory;
use GuzzleHttp\Psr7\Request;
use SergiX44\Nutgram\Configuration;
use SergiX44\Nutgram\Conversations\Conversation;
use SergiX44\Nutgram\Nutgram;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class AddBookConversationIntegrationTest extends KernelTestCase
{
    use ResetDatabase, Factories;

    private Nutgram $bot;
    private AddBookConversation $conversation;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->conversation = self::getContainer()->get(AddBookConversation::class);

        Conversation::refreshOnDeserialize();

        $this->bot = Nutgram::fake(
            config: new Configuration(
                container: self::getContainer()
            )
        );

        $this->bot->onCommand(AddBookConversation::COMMAND_PREFIX, $this->conversation);
        $this->bot->middleware(AdminMiddleware::class);

        $user = UserFactory::createOne(['role' => UserRole::ADMIN]);
        $userDto = new UserDTO(
            id: $user->getId(),
            username: $user->getUsername(),
            displayName: $user->getDisplayName(),
            telegramId: $user->getTelegramId()->getValue(),
            isAdmin: true
        );
        $this->bot->set('user', $userDto);


    }

    public function testFullAddBookFlowSavesToDatabase(): void
    {
        $author = 'Автор Книговов';
        $title = 'Книга книжная';
        $description = 'Описание книги';
        $isbn = '978-0-13-235088-4';

        $this->bot->willStartConversation()
                  ->hearText('/' . AddBookConversation::COMMAND_PREFIX)
                  ->reply()
                  ->assertReply('sendMessage', [
                      'text' => "*Добавление книги*\n\nВведите автора книги:",
                  ]);

        $this->bot->hearText($author)
                  ->reply()
                  ->assertReply('sendMessage', ['text' => 'Введите название книги:']);

        $this->bot->hearText($title)
                  ->reply()
                  ->assertReply('sendMessage', ['text' => 'Введите описание книги:']);

        $this->bot->hearText($description)
                  ->reply()
                  ->assertReply('sendMessage', ['text' => 'Введите ISBN книги:']);

        $this->bot->hearText($isbn)
                  ->reply()
                  ->assertRaw(function (Request $request) use ($title, $author, $isbn) {
                      $content = json_decode((string)$request->getBody(), true, flags: JSON_THROW_ON_ERROR);
                      $text = $content['text'];

                      $this->assertStringContainsString($title, $text);
                      $this->assertStringContainsString($author, $text);
                      $this->assertStringContainsString($isbn, $text);

                      $this->assertArrayHasKey('reply_markup', $content);
                      return true;
                  });

        BookFactory::assert()->notExists(['title' => $title, 'author' => $author]);

        $this->bot->hearCallbackQueryData(AddBookConversation::COMMAND_PREFIX . '_confirm:yes')
                  ->reply();


        BookFactory::assert()->exists(['title' => $title, 'author' => $author]);
    }

    public function testValidationPreventsDatabaseInsert(): void
    {
        $this->bot->willStartConversation()
                  ->hearText('/' . AddBookConversation::COMMAND_PREFIX)->reply(); // Автор
        $this->bot->hearText('Автор')->reply(); // Название
        $this->bot->hearText('Название')->reply(); // Описание
        $this->bot->hearText('Описание')->reply(); // ISBN

        // невалидный ISBN
        $invalidIsbn = 'invalid-isbn';
        $this->bot->hearText($invalidIsbn)
                  ->reply()
                  ->assertReply('sendMessage', [
                      'text' => 'Invalid ISBN: invalid-isbn',
                  ]);

        BookFactory::assert()->notExists(['title' => 'Название', 'author' => 'Автор'], "Книга с неправильным ISBN не должна быть добавлена в БД");

    }
}
