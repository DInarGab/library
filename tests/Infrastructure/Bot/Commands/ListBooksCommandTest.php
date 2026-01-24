<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Tests\Infrastructure\Bot\Commands;

use Dinargab\LibraryBot\Infrastructure\Bot\Commands\BookDetailPageCommand;
use Dinargab\LibraryBot\Infrastructure\Bot\Commands\ListBooksCommand;
use Dinargab\LibraryBot\Tests\Factory\Book\Entity\BookFactory;
use Dinargab\LibraryBot\Tests\Helper\PaginationAssertTrait;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use SergiX44\Nutgram\Nutgram;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class ListBooksCommandTest extends KernelTestCase
{
    use ResetDatabase, Factories, PaginationAssertTrait;

    private EntityManagerInterface $entityManager;
    private ListBooksCommand $command;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->entityManager  = self::getContainer()->get(EntityManagerInterface::class);
        $this->commandHandler = self::getContainer()->get(ListBooksCommand::class);

        $this->bot = Nutgram::fake();
        $this->bootBotLogic($this->bot);
    }

    private function bootBotLogic(Nutgram $bot): void
    {
        $bot->onCallbackQueryData(ListBooksCommand::COMMAND_PREFIX . ':{page}', $this->commandHandler);
        $bot->onCommand(ListBooksCommand::COMMAND_PREFIX, $this->commandHandler);
    }


    public function testNoBooks(): void
    {
        $this->bot->hearText("/" . ListBooksCommand::COMMAND_PREFIX)
                  ->reply()
                  ->assertReply('sendMessage', [
                      'text'       => '*Библиотека пуста*' . PHP_EOL . PHP_EOL . 'Книги пока не добавлены.',
                      'parse_mode' => 'Markdown',
                  ]);
    }

    #[DataProvider('pageProvider')]
    public function testBooksList(int $page): void
    {
        $books = BookFactory::new()
                            ->withCopies(1)
                            ->many(15)
                            ->create();

        $reversedBooks          = array_reverse($books);
        $expectedFirstPageBooks = array_slice($reversedBooks, ($page - 1) * ListBooksCommand::PER_PAGE, ListBooksCommand::PER_PAGE);

        if ($page > 1) {
            $hears = $this->bot->hearCallbackQueryData(ListBooksCommand::COMMAND_PREFIX . ":$page");
        } else {
            $hears = $this->bot->hearText("/" . ListBooksCommand::COMMAND_PREFIX);
        }
        $hears
            ->reply()
            ->assertRaw(function (Request $request) use ($expectedFirstPageBooks, $page) {
                $content        = json_decode((string)$request->getBody(), true, flags: JSON_THROW_ON_ERROR);
                $markup         = $content['reply_markup'];
                $keyboardMarkup = $markup['inline_keyboard'];
                //Проверяем, что в кнопках правильные книги стоят
                foreach ($expectedFirstPageBooks as $index => $book) {
                    $buttonRow = $keyboardMarkup[$index] ?? null;
                    $this->assertNotNull($buttonRow, "Отсутствует ряд кнопок для книги index $index");
                    $button = $buttonRow[0];
                    $this->assertStringContainsString($book->getTitle(), $button['text']);
                    $this->assertEquals(
                        BookDetailPageCommand::COMMAND_PREFIX . ':' . $book->getId(),
                        $button['callback_data']
                    );
                }
                // Проверяем правильность пагинации
                $this->assertPagination(
                    markup: $markup,
                    currentPage: $page,
                    totalPages: 3, // 15 книг / 5 на стр = 3 страницы
                    commandPrefix: ListBooksCommand::COMMAND_PREFIX
                );

                return true;
            }, index: 0);
    }

    public static function pageProvider(): array
    {
        return [
            'first-page' => [1],
            'second-page' => [2],
            'last-page' => [3],
        ];
    }

}
