<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Tests\Infrastructure\Bot\Commands\Admin;

use Dinargab\LibraryBot\Application\Shared\DTO\UserDTO;
use Dinargab\LibraryBot\Infrastructure\Bot\Commands\Admin\DeleteBookCommand;
use Dinargab\LibraryBot\Infrastructure\Bot\Middlewares\AdminMiddleware;
use Dinargab\LibraryBot\Tests\Factory\Book\Entity\BookFactory;
use Dinargab\LibraryBot\Tests\Factory\Lending\Entity\LendingFactory;
use Dinargab\LibraryBot\Tests\Factory\User\Entity\UserFactory;
use GuzzleHttp\Psr7\Request;
use SergiX44\Nutgram\Nutgram;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class DeleteBookCommandTest extends KernelTestCase
{
    use ResetDatabase, Factories;

    private DeleteBookCommand $commandHandler;
    private Nutgram $bot;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->commandHandler = self::getContainer()->get(DeleteBookCommand::class);
        $this->bot = Nutgram::fake();

        $this->bot->onCallbackQueryData(DeleteBookCommand::COMMAND_PREFIX . ':{bookId}', $this->commandHandler);
        $this->bot->middleware(AdminMiddleware::class);
        $user = UserFactory::new()->create();
        $this->bot->set('user', new UserDTO(
            $user->getId(),
            $user->getDisplayName(),
            $user->getUsername(),
            $user->getTelegramId()->getValue(),
            true,
        ));
    }

    public function testDeleteBookSuccess(): void
    {
        $book = BookFactory::createOne();
        $bookId = $book->getId();

        BookFactory::assert()->count(1);

        $this->bot->hearCallbackQueryData(DeleteBookCommand::COMMAND_PREFIX . ":$bookId")
                  ->reply()
                  ->assertReply('editMessageText', [
                      'text' => 'Книга успешно удалена',
                  ])
                  ->assertRaw(function (Request $request) {
                      $content = json_decode((string)$request->getBody(), true, flags: JSON_THROW_ON_ERROR);
                      $keyboard = $content['reply_markup']['inline_keyboard'];

                      $this->assertNotEmpty($keyboard);
                      $this->assertEquals('❌ Закрыть', $keyboard[0][0]['text']);
                      $this->assertEquals('close', $keyboard[0][0]['callback_data']);

                      return true;
                  });

        BookFactory::assert()->count(0);
    }

    public function testDeleteBookHandlesDomainException(): void
    {
        $lending = LendingFactory::new()->create();
        $book = $lending->getBookCopy()->getBook();
        $bookId = $book->getId();

        $errorMessage = "Невозможно удалить книгу '{$book->getTitle()}': есть история выдачи";

        $this->bot->hearCallbackQueryData(DeleteBookCommand::COMMAND_PREFIX . ":$bookId")
                  ->reply()
                  ->assertReply('editMessageText', [
                      'text' => $errorMessage,
                  ])
                  ->assertRaw(function (Request $request) {
                      $content = json_decode((string)$request->getBody(), true, flags: JSON_THROW_ON_ERROR);
                      $callbackData = $content['reply_markup']['inline_keyboard'][0][0]['callback_data'];
                      $this->assertEquals('close', $callbackData);
                      return true;
                  });
    }
}
