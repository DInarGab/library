<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Tests\Infrastructure\Bot\Commands;

use Dinargab\LibraryBot\Application\Book\DTO\ParseBookRequestDTO;
use Dinargab\LibraryBot\Application\Book\UseCase\ParseBookUseCase;
use Dinargab\LibraryBot\Application\Shared\DTO\ParsedBookDTO;
use Dinargab\LibraryBot\Application\Shared\DTO\UserDTO;
use Dinargab\LibraryBot\Infrastructure\Bot\Commands\SuggestBookConversation;
use Dinargab\LibraryBot\Tests\Factory\Book\Entity\BookSuggestionFactory;
use Dinargab\LibraryBot\Tests\Factory\User\Entity\UserFactory;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\MockObject\MockObject;
use SergiX44\Nutgram\Configuration;
use SergiX44\Nutgram\Conversations\Conversation;
use SergiX44\Nutgram\Nutgram;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class SuggestBookConversationTest extends KernelTestCase
{
    use ResetDatabase, Factories;

    private Nutgram $bot;
    private MockObject $parseBookUseCaseMock;

    private SuggestBookConversation $conversation;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->parseBookUseCaseMock = $this->createMock(ParseBookUseCase::class);
        self::getContainer()->set(ParseBookUseCase::class, $this->parseBookUseCaseMock);
        $this->conversation  = self::getContainer()->get(SuggestBookConversation::class);


        Conversation::refreshOnDeserialize();

        $this->bot = Nutgram::fake(
            config: new Configuration(
                container: self::getContainer()
            )
        );

        $this->bot->onCommand(SuggestBookConversation::COMMAND_PREFIX, $this->conversation);
        $this->bot->onCallbackQueryData(SuggestBookConversation::COMMAND_PREFIX, $this->conversation);


        $user    = UserFactory::createOne();
        $userDto = new UserDTO(
            id: $user->getId(),
            username: $user->getUsername(),
            displayName: $user->getDisplayName(),
            telegramId: $user->getTelegramId()->getValue(),
            isAdmin: false
        );
        $this->bot->set('user', $userDto);
    }

    public function testManualSuggestionFlow(): void
    {
        $this->bot->willStartConversation()
                  ->hearText('/' . SuggestBookConversation::COMMAND_PREFIX)
                  ->reply()
                  ->assertReply('sendMessage', [
                      'text'       => "*Предложить книгу*" . PHP_EOL . PHP_EOL . "Выберите способ добавления:",
                      'parse_mode' => 'Markdown',
                  ]);

        $this->bot->hearCallbackQueryData(
            SuggestBookConversation::COMMAND_PREFIX . "_" . SuggestBookConversation::TYPE_MANUAL_CALLBACK
        )
                  ->reply()
                  ->assertActiveConversation();

        $this->bot->hearText('Властелин колец')
                  ->reply()
                  ->assertReply('sendMessage', [
                      'text' => 'Введите автора книги:',
                  ]);

        $this->bot->hearText('Толкин')
                  ->reply()
                  ->assertReply('sendMessage', [
                      'text' => "Добавить комментарий? (необязательно)\n\nОтправьте комментарий или нажмите \"Пропустить\":",
                  ]);

        $this->bot->hearCallbackQueryData(
            SuggestBookConversation::COMMAND_PREFIX . "_" . SuggestBookConversation::SKIP_COMMENT_CALLBACK
        )
                  ->reply()
                  ->assertCalled('answerCallbackQuery');


        $this->bot->hearCallbackQueryData(SuggestBookConversation::COMMAND_PREFIX . '_confirm:yes')
                  ->assertRaw(function (Request $request) {
                      $content = json_decode((string)$request->getBody(), true, flags: JSON_THROW_ON_ERROR);

                      return true;
                  })
                  ->reply();


        BookSuggestionFactory::assert()->exists(
            [
                'title'  => 'Властелин колец',
                'author' => 'Толкин',
                'user'   => $this->bot->get('user')->id
            ]
        );
    }

    public function testUrlSuggestionFlowWithSuccessfulParse(): void
    {
        $url = 'https://example.com/book/123';

        $parsedBookDto = new ParsedBookDTO(
            title: 'Новое предложение',
            author: 'Автор Книговов',
            isbn: '9780132350884',
            url: $url,
            description: 'Описание'
        );
        $this->parseBookUseCaseMock
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(fn(ParseBookRequestDTO $dto) => $dto->url === $url))
            ->willReturn(
                $parsedBookDto
            );

        $this->bot->willStartConversation()
                  ->hearText('/' . SuggestBookConversation::COMMAND_PREFIX)->reply();

        $this->bot->hearCallbackQueryData(
            SuggestBookConversation::COMMAND_PREFIX . "_" . SuggestBookConversation::TYPE_URL_CALLBACK
        )
                  ->reply();

        $this->bot->hearText($url)
                  ->reply()
                  ->assertRaw(function (Request $request) {
                      $content = json_decode((string)$request->getBody(), true, flags: JSON_THROW_ON_ERROR);
                      $this->assertStringContainsString('Добавить комментарий?', $content['text']);

                      return true;
                  }, 0,);

        $this->bot->hearText('Отличная книга')
                  ->reply()
                  ->assertRaw(function (Request $request) {
                      $content = json_decode((string)$request->getBody(), true);
                      $text    = $content['text'];

                      $this->assertStringContainsString('Новое предложение', $text);
                      $this->assertStringContainsString('Автор Книговов', $text);
                      $this->assertStringContainsString('Отличная книга', $text);
                      $this->assertStringContainsString('9780132350884', $text);

                      return true;
                  });

        $this->bot->hearCallbackQueryData(SuggestBookConversation::COMMAND_PREFIX . '_confirm:yes')
                  ->reply();

        BookSuggestionFactory::assert()->exists([
            'title'     => $parsedBookDto->title,
            'sourceUrl' => $parsedBookDto->url,
            'author'    => $parsedBookDto->author
        ]);
    }

    public function testUrlSuggestionFlowWithFailedParseFallback(): void
    {
        $url = 'https://unknown-site.com/book';

        $author = 'Автор Авторович Второй';
        $title = 'Автор Авторович Биография';


        // Mock the parser to return NULL (failure)
        $this->parseBookUseCaseMock
            ->expects($this->once())
            ->method('__invoke')
            ->willReturn(null);

        $this->bot->willStartConversation()
                  ->hearText('/' . SuggestBookConversation::COMMAND_PREFIX)
                  ->reply();

        $this->bot->hearCallbackQueryData(SuggestBookConversation::COMMAND_PREFIX . "_" . SuggestBookConversation::TYPE_URL_CALLBACK)
                  ->reply();

        $this->bot->hearText($url)
                  ->reply()
                  ->assertReply('sendMessage', [
                      'text' => "Не удалось спарсить содержимое ссылки.\n\n Введите Название книги",
                  ]);

        $this->bot->hearText($title)
                  ->reply()
                  ->assertReply('sendMessage', [
                      'text' => 'Введите автора книги:',
                  ]);

        $this->bot->hearText($author)
                  ->reply()
            ->assertReply('sendMessage', ['text' => "Добавить комментарий? (необязательно)\n\nОтправьте комментарий или нажмите \"Пропустить\":"]);

        $this->bot->hearCallbackQueryData(SuggestBookConversation::COMMAND_PREFIX . "_" . SuggestBookConversation::SKIP_COMMENT_CALLBACK)
                  ->reply();

        $this->bot->hearCallbackQueryData(SuggestBookConversation::COMMAND_PREFIX . '_confirm:yes')
                  ->reply();


        BookSuggestionFactory::assert()->exists([
            'title'     => $title,
            'author'    => $author
        ]);

    }

    public function testInvalidUrlValidation(): void
    {
        $this->bot->willStartConversation()->hearText('/' . SuggestBookConversation::COMMAND_PREFIX)->reply();
        $this->bot->hearCallbackQueryData(SuggestBookConversation::COMMAND_PREFIX . "_" . SuggestBookConversation::TYPE_URL_CALLBACK)->reply();

        $this->bot->hearText('not-a-url')
                  ->reply()
                  ->assertReply('sendMessage', [
                      'text' => 'Это не похоже на ссылку. Пожалуйста, отправьте корректную ссылку:',
                  ]);

        $this->parseBookUseCaseMock->method('__invoke')->willReturn(new ParsedBookDTO('Title', 'Description', 'https://google.com', 'Author', ''));

        $this->bot->hearText('https://google.com')
                  ->reply()
                  ->assertReply('sendMessage', [
                      'text' => "Добавить комментарий? (необязательно)\n\nОтправьте комментарий или нажмите \"Пропустить\":",
                  ]);
    }
}
