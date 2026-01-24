<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Tests\Infrastructure\Bot\Commands;

use Dinargab\LibraryBot\Application\Shared\DTO\UserDTO;
use Dinargab\LibraryBot\Domain\Lending\Entity\Lending;
use Dinargab\LibraryBot\Infrastructure\Bot\Commands\LendingsDetailPageCommand;
use Dinargab\LibraryBot\Infrastructure\Bot\Commands\ListLendingsCommand;
use Dinargab\LibraryBot\Tests\Factory\Book\Entity\BookCopyFactory;
use Dinargab\LibraryBot\Tests\Factory\Lending\Entity\LendingFactory;
use Dinargab\LibraryBot\Tests\Factory\User\Entity\UserFactory;
use Dinargab\LibraryBot\Tests\Helper\PaginationAssertTrait;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use SergiX44\Nutgram\Nutgram;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class ListLendingsCommandTest extends KernelTestCase
{
    use ResetDatabase, Factories, PaginationAssertTrait;

    private const LENDINGS_NUMBER = 15;
    private ListLendingsCommand $commandHandler;
    private Nutgram $bot;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->commandHandler = self::getContainer()->get(ListLendingsCommand::class);

        $this->bot = Nutgram::fake();
        $this->bootBotLogic($this->bot);
    }

    private function bootBotLogic(Nutgram $bot): void
    {
        $bot->onCallbackQueryData(ListLendingsCommand::COMMAND_PREFIX . ':{page}', $this->commandHandler);
        $bot->onCommand(ListLendingsCommand::COMMAND_PREFIX, $this->commandHandler);
    }

    private function injectAdminUserIntoBot(): void
    {
        $user    = UserFactory::createOne();
        $userDto = new UserDTO(
            id: $user->getId(),
            username: $user->getUsername(),
            displayName: $user->getDisplayName(),
            telegramId: $user->getTelegramId()->getValue(),
            isAdmin: true
        );
        $this->bot->set('user', $userDto);
    }

    public function testNoLendings(): void
    {
        $this->injectAdminUserIntoBot();

        $this->bot->hearText("/" . ListLendingsCommand::COMMAND_PREFIX)
                  ->reply()
                  ->assertReply('sendMessage', [
                      'text'       => '*Нет выданных книг*' . PHP_EOL . PHP_EOL,
                      'parse_mode' => 'Markdown',
                  ]);
    }

    #[DataProvider('pageProvider')]
    public function testLendingsList(int $page): void
    {

        $this->injectAdminUserIntoBot();

        $lendings = LendingFactory::new()->with([
            'user' => UserFactory::createOne(),
            'bookCopy' => BookCopyFactory::createOne()
        ])->many(self::LENDINGS_NUMBER)->create();
        $expectedFirstPageLendings = array_slice($lendings, ($page - 1) * ListLendingsCommand::PER_PAGE, 5);

        if ($page > 1) {
            $hears = $this->bot->hearCallbackQueryData(ListLendingsCommand::COMMAND_PREFIX . ":$page");
        } else {
            $hears = $this->bot->hearText("/" . ListLendingsCommand::COMMAND_PREFIX);
        }

        $hears
            ->reply()
            ->assertRaw(function (Request $request) use ($expectedFirstPageLendings, $page) {
                $content        = json_decode((string)$request->getBody(), true, flags: JSON_THROW_ON_ERROR);
                $markup         = $content['reply_markup'];
                $keyboardMarkup = $markup['inline_keyboard'];

                // Проверяем, что в кнопках правильные данные о выдачах
                /**
                 * @var  $index
                 * @var Lending $lending
                 */
                foreach ($expectedFirstPageLendings as $index => $lending) {

                    $buttonRow = $keyboardMarkup[$index] ?? null;
                    $this->assertNotNull($buttonRow, "Отсутствует ряд кнопок для выдачи index $index на странице $page");
                    $button = $buttonRow[0];

                    $this->assertStringContainsString($lending->getUser()->getDisplayName(), $button['text'], 'В кнопке нет Имени кому выдана');
                    $this->assertStringContainsString($lending->getBookCopy()->getBook()->getTitle(), $button['text'], 'В кнопке отсутствует Название книги');
                    // Проверка callback_data
                    $this->assertEquals(
                        LendingsDetailPageCommand::COMMAND_PREFIX . ':' . $lending->getId(),
                        $button['callback_data']
                    );
                }

                $this->assertPagination(
                    markup: $markup,
                    currentPage: $page,
                    totalPages: self::LENDINGS_NUMBER / ListLendingsCommand::PER_PAGE,
                    commandPrefix: ListLendingsCommand::COMMAND_PREFIX
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
