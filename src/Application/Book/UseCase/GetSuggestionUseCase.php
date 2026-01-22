<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Application\Book\UseCase;

use Dinargab\LibraryBot\Application\Book\DTO\GetSuggestionRequestDTO;
use Dinargab\LibraryBot\Application\Shared\DTO\SuggestionDTO;
use Dinargab\LibraryBot\Domain\Exception\BookSuggestionNotFoundException;
use Dinargab\LibraryBot\Infrastructure\Persistence\Repository\BookSuggestionRepository;

class GetSuggestionUseCase
{
    public function __construct(
        private BookSuggestionRepository $bookSuggestionRepository,
    ) {
    }

    public function __invoke(
        GetSuggestionRequestDTO $getSuggestionRequestDTO,
    ): ?SuggestionDTO {
        $suggestionInfo = $this->bookSuggestionRepository->findById($getSuggestionRequestDTO->suggestionId);
        if ($suggestionInfo === null) {
            throw new BookSuggestionNotFoundException("Suggestion not found");
        }

        return SuggestionDTO::fromEntity($suggestionInfo);
    }
}
