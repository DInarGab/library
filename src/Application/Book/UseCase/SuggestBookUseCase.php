<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Application\Book\UseCase;

use Dinargab\LibraryBot\Application\Book\DTO\SuggestBookRequestDTO;
use Dinargab\LibraryBot\Application\Shared\DTO\SuggestionDTO;
use Dinargab\LibraryBot\Domain\Book\Entity\BookSuggestion;
use Dinargab\LibraryBot\Domain\Book\Factory\BookParserFactoryInterface;
use Dinargab\LibraryBot\Domain\Book\Repository\BookSuggestionRepositoryInterface;
use Dinargab\LibraryBot\Domain\Exception\UserNotFoundException;
use Dinargab\LibraryBot\Domain\User\Repository\UserRepositoryInterface;

class SuggestBookUseCase
{

    public function __construct(
        private BookSuggestionRepositoryInterface $suggestionRepository,
        private UserRepositoryInterface $userRepository,
        private BookParserFactoryInterface $parserFactory
    ) {}

    public function __invoke(
        SuggestBookRequestDTO $suggestBookRequestDTO
    )
    {
        $user = $this->userRepository->findByTelegramId($suggestBookRequestDTO->telegramId);

        if ($user === null) {
            throw new UserNotFoundException("User not found");
        }

        $parsedData = null;

        try {
            $parser = $this->parserFactory->createFromUrl($suggestBookRequestDTO->url);
            $parsedBook = $parser->parseContent();
        } catch (\Exception $e) {
            // Парсинг не сработал, получится сохранить только URL
            // Скорее всего это случится в 100% случаев)
        }

        $suggestion = new BookSuggestion(
            user: $user,
            title: $parsedBook->title,
            author: $parsedBook->author,
            isbn: $parsedBook->isbn,
            sourceUrl: $suggestBookRequestDTO->url,
            parsedData: $parsedData
        );

        $this->suggestionRepository->save($suggestion);

        return SuggestionDTO::fromEntity($suggestion);
    }
}
