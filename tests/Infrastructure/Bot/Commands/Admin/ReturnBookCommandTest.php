<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Tests\Infrastructure\Bot\Commands\Admin;

use Dinargab\LibraryBot\Application\Shared\DTO\UserDTO;
use Dinargab\LibraryBot\Domain\Lending\ValueObject\LendingStatus;
use Dinargab\LibraryBot\Infrastructure\Bot\Commands\Admin\ReturnBookCommand;
use Dinargab\LibraryBot\Infrastructure\Bot\Commands\ListLendingsCommand;
use Dinargab\LibraryBot\Infrastructure\Bot\Middlewares\AdminMiddleware;
use Dinargab\LibraryBot\Tests\Factory\Lending\Entity\LendingFactory;
use Dinargab\LibraryBot\Tests\Factory\User\Entity\UserFactory;
use GuzzleHttp\Psr7\Request;
use SergiX44\Nutgram\Nutgram;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class ReturnBookCommandTest extends KernelTestCase
{
    use ResetDatabase, Factories;

    private ReturnBookCommand $commandHandler;
    private Nutgram $bot;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->commandHandler = self::getContainer()->get(ReturnBookCommand::class);
        $this->bot = Nutgram::fake();

        $this->bot->onCallbackQueryData(ReturnBookCommand::RETURN_BOOK_PREFIX . '{lendingId}', $this->commandHandler);
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

    public function testReturnBookSuccess(): void
    {
        $lending = LendingFactory::new()->create([
            'status' => LendingStatus::LENT,
        ]);
        $lendingId = $lending->getId();
        $book = $lending->getBookCopy()->getBook();

        $this->assertEquals(LendingStatus::LENT, $lending->getStatus());

        $this->bot->hearCallbackQueryData(ReturnBookCommand::RETURN_BOOK_PREFIX . $lendingId)
                  ->reply()
                  ->assertReply('editMessageText')
                  ->assertRaw(function (Request $request) use ($book) {
                      $content = json_decode((string)$request->getBody(), true, flags: JSON_THROW_ON_ERROR);
                      $text = $content['text'];
                      $keyboard = $content['reply_markup']['inline_keyboard'];

                      // Проверяем содержимое сообщения
                      $this->assertStringContainsString('Книга возвращена', $text);
                      $this->assertStringContainsString($book->getAuthor(), $text);
                      $this->assertStringContainsString($book->getTitle(), $text);
                      $this->assertStringContainsString('Книга выдана:', $text);
                      $this->assertStringContainsString('Возращена:', $text);
                      $this->assertNotEmpty($keyboard);
                      $this->assertCount(2, $keyboard[0]);

                      $this->assertEquals('Назад', $keyboard[0][0]['text']);
                      $this->assertStringStartsWith(ListLendingsCommand::COMMAND_PREFIX . ':', $keyboard[0][0]['callback_data']);

                      $this->assertEquals('Закрыть', $keyboard[0][1]['text']);
                      $this->assertEquals('close', $keyboard[0][1]['callback_data']);

                      return true;
                  });

        // Проверяем, что статус изменился на RETURNED
        LendingFactory::assert()->exists(['id' => $lending->getId(), 'status' => LendingStatus::RETURNED]);
    }

    public function testReturnBookAlreadyReturned(): void
    {
        // Создаем уже возвращенную выдачу
        $lending = LendingFactory::new()->create([
            'status' => LendingStatus::RETURNED,
        ]);
        $lendingId = $lending->getId();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('This lending cannot be returned');

        $this->bot->hearCallbackQueryData(ReturnBookCommand::RETURN_BOOK_PREFIX . $lendingId)
                  ->reply();
    }

    public function testReturnBookWithOverdueStatus(): void
    {
        // Создаем просроченную выдачу (OVERDUE можно вернуть)
        $lending = LendingFactory::new()->create([
            'status' => LendingStatus::OVERDUE,
        ]);
        $lendingId = $lending->getId();

        // Если OVERDUE можно вернуть
        $this->bot->hearCallbackQueryData(ReturnBookCommand::RETURN_BOOK_PREFIX . $lendingId)
                  ->reply()
                  ->assertReply('editMessageText')
                  ->assertRaw(function (Request $request) {
                      $content = json_decode((string)$request->getBody(), true, flags: JSON_THROW_ON_ERROR);
                      $this->assertStringContainsString('Книга возвращена', $content['text']);
                      return true;
                  });

        LendingFactory::assert()->exists(['id' => $lending->getId(), 'status' => LendingStatus::RETURNED]);
    }

    public function testReturnBookNotFound(): void
    {
        $nonExistentLendingId = 99999;

        $this->expectException(\InvalidArgumentException::class); // Или конкретный тип исключения

        $this->bot->hearCallbackQueryData(ReturnBookCommand::RETURN_BOOK_PREFIX . $nonExistentLendingId)
                  ->reply();
    }

}
