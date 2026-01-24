<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Tests\Infrastructure\Bot\Commands;

use Dinargab\LibraryBot\Application\Shared\DTO\UserDTO;
use Dinargab\LibraryBot\Domain\Book\ValueObject\BookSuggestionStatus;
use Dinargab\LibraryBot\Infrastructure\Bot\Commands\Admin\SuggestionProcessingCommand;
use Dinargab\LibraryBot\Infrastructure\Bot\Commands\ListSuggestionsCommand;
use Dinargab\LibraryBot\Infrastructure\Bot\Commands\SuggestionDetailPageCommand;
use Dinargab\LibraryBot\Tests\Factory\Book\Entity\BookSuggestionFactory;
use Dinargab\LibraryBot\Tests\Factory\User\Entity\UserFactory;
use GuzzleHttp\Psr7\Request;
use SergiX44\Nutgram\Nutgram;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class SuggestionDetailPageCommandTest extends KernelTestCase
{
    use ResetDatabase, Factories;

    private SuggestionDetailPageCommand $commandHandler;
    private Nutgram $bot;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->commandHandler = self::getContainer()->get(SuggestionDetailPageCommand::class);

        // Initialize Fake Bot
        $this->bot = Nutgram::fake();
        $this->bootBotLogic($this->bot);
    }

    private function bootBotLogic(Nutgram $bot): void
    {
        $bot->onCallbackQueryData(SuggestionDetailPageCommand::SUGGESTION_DETAIL_CALLBACK . ':{suggestionId}', $this->commandHandler);
    }

    public function testSuggestionNotFound(): void
    {
        $this->mockUser(isAdmin: true);

        $this->bot->hearCallbackQueryData(SuggestionDetailPageCommand::SUGGESTION_DETAIL_CALLBACK . ':99999')
                  ->reply()
                  ->assertReply('sendMessage', [
                      'text' => 'Предложение не найдено',
                  ]);
    }

    public function testSuggestionDetailsAsRegularUser(): void
    {
        // 1. Создаем данные
        $userWhoSuggested = UserFactory::new()->create(['username' => 'suggester_user']);
        $comment = 'Купите, ну пожалуйста';
        $suggestion = BookSuggestionFactory::new()->create([
            'user' => $userWhoSuggested,
            'sourceUrl' => 'http://example.com',
            'comment' => $comment,
            'status' => BookSuggestionStatus::PENDING,
        ]);

        // Обычный пользователь
        $this->mockUser(isAdmin: false);

        $this->bot->hearCallbackQueryData(SuggestionDetailPageCommand::SUGGESTION_DETAIL_CALLBACK . ':' . $suggestion->getId())
                  ->reply()
                  ->assertRaw(function (Request $request) use ($suggestion, $comment, $userWhoSuggested) {
                      $content = json_decode((string)$request->getBody(), true, flags: JSON_THROW_ON_ERROR);
                      $text = $content['text'];

                      // Проверка текста
                      $this->assertStringContainsString($suggestion->getTitle(), $text);
                      $this->assertStringContainsString($suggestion->getAuthor(), $text);
                      $this->assertStringContainsString((string) $suggestion->getIsbn(), $text);
                      $this->assertStringContainsString($suggestion->getSourceUrl(), $text);
                      $this->assertStringContainsString($comment, $text);
                      $this->assertStringContainsString($userWhoSuggested->getDisplayName(), $text);

                      // Проверка клавиатуры
                      $keyboard = $content['reply_markup']['inline_keyboard'];
                      $buttons = array_merge(...$keyboard);
                      $callbacks = array_column($buttons, 'callback_data');

                      // Должна быть навигация
                      $this->assertContains(ListSuggestionsCommand::COMMAND_PREFIX . ":1", $callbacks);

                      // НЕ должно быть админских кнопок
                      $approveCallbackPrefix = SuggestionProcessingCommand::PROCESS_SUGGESTION_CALLBACK;
                      foreach ($callbacks as $callback) {
                          $this->assertStringNotContainsString($approveCallbackPrefix, $callback, 'Не администратор не должен видеть кнопки согласования');
                      }

                      return true;
                  }, message: 'editMessageText');
    }

    public function testSuggestionDetailsAsAdmin(): void
    {
        // 1. Создаем данные
        $suggestion = BookSuggestionFactory::new()->create([
            'title' => 'Проверка Админских полномочий',
            'status' => BookSuggestionStatus::PENDING,
        ]);

        // 2. Являемся админом
        $this->mockUser(isAdmin: true);

        // 3. Проверяем наличие кнопок действий
        $this->bot->hearCallbackQueryData(SuggestionDetailPageCommand::SUGGESTION_DETAIL_CALLBACK . ':' . $suggestion->getId())
                  ->reply()
                  ->assertRaw(function (Request $request) use ($suggestion) {
                      $content = json_decode((string)$request->getBody(), true, flags: JSON_THROW_ON_ERROR);
                      $keyboard = $content['reply_markup']['inline_keyboard'];

                      $buttons = array_merge(...$keyboard);
                      $callbacks = array_column($buttons, 'callback_data');

                      $baseCallback = SuggestionProcessingCommand::PROCESS_SUGGESTION_CALLBACK . ":{$suggestion->getId()}";

                      // Кнопка Принять
                      $this->assertContains(
                          $baseCallback . ':' . BookSuggestionStatus::APPROVED->value,
                          $callbacks
                      );

                      // Кнопка Отклонить
                      $this->assertContains(
                          $baseCallback . ':' . BookSuggestionStatus::REJECTED->value,
                          $callbacks
                      );

                      return true;
                  });
    }

    public function testRejectedSuggestionShowsAdminComment(): void
    {
        // 1. Создаем отклоненное предложение с комментарием
        $adminComment = 'Не нужна в нашей библиотеке';
        $suggestion = BookSuggestionFactory::new()->create([
            'title' => 'Отклоненное предложение',
            'status' => BookSuggestionStatus::REJECTED,
            'adminComment' => $adminComment,
        ]);

        $this->mockUser(isAdmin: false); // Любой пользователь должен видеть причину отказа

        $this->bot->hearCallbackQueryData(SuggestionDetailPageCommand::SUGGESTION_DETAIL_CALLBACK . ':' . $suggestion->getId())
                  ->reply()
                  ->assertRaw(function (Request $request) use ($adminComment) {
                      $content = json_decode((string)$request->getBody(), true, flags: JSON_THROW_ON_ERROR);
                      $text = $content['text'];

                      $this->assertStringContainsString('*Причина отказа*:', $text);
                      $this->assertStringContainsString($adminComment, $text);

                      // Убеждаемся, что не выводится лейбл для Approved
                      $this->assertStringNotContainsString('*Комментарий администратора*:', $text);

                      return true;
                  });
    }

    public function testApprovedSuggestionShowsAdminComment(): void
    {
        // 1. Создаем принятое предложение с комментарием
        $adminComment = 'Скоро купим';
        $suggestion = BookSuggestionFactory::new()->create([

            'title' => 'Принятое предложение',
            'status' => BookSuggestionStatus::APPROVED,
            'adminComment' => $adminComment,
        ]);

        $this->mockUser(isAdmin: false);

        $this->bot->hearCallbackQueryData(SuggestionDetailPageCommand::SUGGESTION_DETAIL_CALLBACK . ':' . $suggestion->getId())
                  ->reply()
                  ->assertRaw(function (Request $request) use ($adminComment) {
                      $content = json_decode((string)$request->getBody(), true, flags: JSON_THROW_ON_ERROR);
                      $text = $content['text'];

                      $this->assertStringContainsString('*Комментарий администратора*:', $text);
                      $this->assertStringContainsString($adminComment, $text);

                      return true;
                  });
    }

    private function mockUser(bool $isAdmin): void
    {
        $userDto = new UserDTO(
            id: 1,
            displayName: 'Test User',
            username: 'testuser',
            telegramId: '123456789',
            isAdmin: $isAdmin
        );
        $this->bot->set('user', $userDto);
    }
}
