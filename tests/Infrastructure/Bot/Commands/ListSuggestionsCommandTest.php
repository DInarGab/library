<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Tests\Infrastructure\Bot\Commands;

use Dinargab\LibraryBot\Application\Shared\DTO\UserDTO;
use Dinargab\LibraryBot\Domain\Book\ValueObject\BookSuggestionStatus;
use Dinargab\LibraryBot\Infrastructure\Bot\Commands\ListSuggestionsCommand;
use Dinargab\LibraryBot\Infrastructure\Bot\Commands\SuggestionDetailPageCommand;
use Dinargab\LibraryBot\Tests\Factory\Book\Entity\BookSuggestionFactory;
use Dinargab\LibraryBot\Tests\Factory\User\Entity\UserFactory;
use Dinargab\LibraryBot\Tests\Helper\PaginationAssertTrait;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use SergiX44\Nutgram\Nutgram;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class ListSuggestionsCommandTest extends KernelTestCase
{
    use ResetDatabase, Factories, PaginationAssertTrait;

    private ListSuggestionsCommand $commandHandler;
    private Nutgram $bot;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->commandHandler = self::getContainer()->get(ListSuggestionsCommand::class);
        $this->bot            = Nutgram::fake();
        $this->bootBotLogic($this->bot);
    }

    private function mockUser(int $userId, bool $isAdmin): void
    {
        $userDto = new UserDTO(
            id: $userId,
            displayName: 'Test User',
            username: 'testuser',
            telegramId: '123456789',
            isAdmin: $isAdmin
        );
        $this->bot->set('user', $userDto);
    }

    private function bootBotLogic(Nutgram $bot): void
    {
        // Регистрируем обработчики для callback-запросов пагинации и основной команды
        $bot->onCallbackQueryData(ListSuggestionsCommand::COMMAND_PREFIX . ':{page}', $this->commandHandler);
        $bot->onCommand(ListSuggestionsCommand::COMMAND_PREFIX, $this->commandHandler);
    }

    public function testNoSuggestions(): void
    {
        $user = UserFactory::new()->create();
        $this->mockUser($user->getId(), true);
        $this->bot->hearText("/" . ListSuggestionsCommand::COMMAND_PREFIX)
                  ->reply()
                  ->assertReply('sendMessage', [
                      'text'       => '*Предложений нет*',
                      'parse_mode' => 'Markdown',
                  ]);
    }

    #[DataProvider('pageProvider')]
    public function testSuggestionsList(int $page): void
    {
        $user = UserFactory::new()->create();
        $this->mockUser($user->getId(), true);
        $suggestions = BookSuggestionFactory::new()
                                            ->with(['status' => BookSuggestionStatus::PENDING])
                                            ->many(15)
                                            ->create();



        $offset                       = ($page - 1) * ListSuggestionsCommand::PER_PAGE;
        $expectedFirstPageSuggestions = array_slice(
            $suggestions,
            $offset,
            ListSuggestionsCommand::PER_PAGE
        );

        if ($page > 1) {
            $hears = $this->bot->hearCallbackQueryData(ListSuggestionsCommand::COMMAND_PREFIX . ":$page");
        } else {
            $hears = $this->bot->hearText("/" . ListSuggestionsCommand::COMMAND_PREFIX);
        }

        $hears
            ->reply()
            ->assertRaw(function (Request $request) use ($expectedFirstPageSuggestions, $page, $offset) {
                $content        = json_decode((string)$request->getBody(), true, flags: JSON_THROW_ON_ERROR);
                $markup         = $content['reply_markup'];
                $keyboardMarkup = $markup['inline_keyboard'];

                // Проверяем кнопки с предложениями
                foreach ($expectedFirstPageSuggestions as $index => $suggestion) {
                    $buttonRow = $keyboardMarkup[$index] ?? null;
                    $this->assertNotNull($buttonRow, "Отсутствует ряд кнопок для предложения index $index");

                    $button = $buttonRow[0];
                    $this->assertStringContainsString(
                        "{$suggestion->getTitle()} : {$suggestion->getAuthor()}",
                        $button['text']
                    );

                    $this->assertEquals(
                        SuggestionDetailPageCommand::SUGGESTION_DETAIL_CALLBACK . ':' . $suggestion->getId(),
                        $button['callback_data']
                    );
                }

                $this->assertPagination(
                    markup: $markup,
                    currentPage: $page,
                    totalPages: 3,
                    commandPrefix: ListSuggestionsCommand::COMMAND_PREFIX
                );

                return true;
            }, index: 0);
    }

    public static function pageProvider(): array
    {
        return [
            'first-page'  => [1],
            'second-page' => [2],
            'last-page'   => [3],
        ];
    }

    public function testAdminSeesAllPendingSuggestions(): void
    {
        $user = UserFactory::new()->create();
        $this->mockUser($user->getId(), true);


        $userCreator = UserFactory::new()->create();
        //Pending предложения
        $pendingSuggestions = BookSuggestionFactory::new()
                                                   ->with(['status' => BookSuggestionStatus::PENDING])
                                                   ->many(2)
                                                   ->create([
                                                       'user' => $userCreator
                                                   ]);
        $anotherUser = UserFactory::new()->create();
        //И еще одно
        $otherPending = BookSuggestionFactory::new()
                                             ->with(['status' => BookSuggestionStatus::PENDING])
                                             ->create(['user' => $anotherUser]);

        // В списке Админа не должен показаться
        BookSuggestionFactory::new()
                             ->with(['status' => BookSuggestionStatus::APPROVED])
                             ->create(['user' => $user]);

        $expectedSuggestions = array_merge($pendingSuggestions, [$otherPending]);

        $this->bot->hearText("/" . ListSuggestionsCommand::COMMAND_PREFIX)
                  ->reply()
                  ->assertRaw(function (Request $request) use ($expectedSuggestions) {
                      $content        = json_decode((string)$request->getBody(), true, flags: JSON_THROW_ON_ERROR);
                      $keyboardMarkup = $content['reply_markup']['inline_keyboard'];

                      // Approved быть не должно
                      // + 2 кнопки т.к. есть Страница 1/1 и закрыть
                      $this->assertCount(count($expectedSuggestions) + 2, $keyboardMarkup);
                      foreach ($expectedSuggestions as $index => $suggestion) {
                          $button = $keyboardMarkup[$index][0];
                          $this->assertStringContainsString($suggestion->getTitle(), $button['text']);
                          $this->assertEquals(
                              SuggestionDetailPageCommand::SUGGESTION_DETAIL_CALLBACK . ':' . $suggestion->getId(),
                              $button['callback_data']
                          );
                      }

                      return true;
                  });
    }

    public function testUserSeesOwnHistoryOnly(): void
    {

        $user = UserFactory::new()->create();
        $this->mockUser($user->getId(), false);
        // Предложения текущего пользователя (разные статусы)
        $expectedSuggestions = BookSuggestionFactory::new()
                                              ->sequence([
                                                  ['status' => BookSuggestionStatus::PENDING],
                                                  ['status' => BookSuggestionStatus::APPROVED],
                                                  ['status' => BookSuggestionStatus::REJECTED],
                                              ])
                                              ->create(['user' => $user]);

        // Предложения Другого пользователя (не должны быть видны)
        $anotherUser = UserFactory::new()->create();
        BookSuggestionFactory::new()
                             ->with(['status' => BookSuggestionStatus::PENDING])
                             ->create(['user' => $anotherUser]);


        $this->bot->hearText("/" . ListSuggestionsCommand::COMMAND_PREFIX)
                  ->reply()
                  ->assertRaw(function (Request $request) use ($expectedSuggestions) {
                      $content        = json_decode((string)$request->getBody(), true, flags: JSON_THROW_ON_ERROR);
                      $keyboardMarkup = $content['reply_markup']['inline_keyboard'];
                      // Должно быть ровно 3 кнопки (только свои) + 2 кнопки пагинации
                      $this->assertCount(count($expectedSuggestions) + 2, $keyboardMarkup);

                      foreach ($expectedSuggestions as $index => $suggestion) {
                          $button = $keyboardMarkup[$index][0];
                          $this->assertStringContainsString((string)$suggestion->getId(), $button['callback_data']);
                          $this->assertStringContainsString($suggestion->getTitle(), $button['text']);
                      }

                      return true;
                  });
    }

}
