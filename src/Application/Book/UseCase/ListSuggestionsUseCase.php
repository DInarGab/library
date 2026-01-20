<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Application\Book\UseCase;

use Dinargab\LibraryBot\Application\Book\DTO\ListSuggestionRequestDTO;
use Dinargab\LibraryBot\Application\Book\DTO\ListSuggestionResponseDTO;
use Dinargab\LibraryBot\Application\Shared\DTO\SuggestionDTO;
use Dinargab\LibraryBot\Domain\Book\Entity\BookSuggestion;
use Dinargab\LibraryBot\Domain\Book\Repository\BookSuggestionRepositoryInterface;
use Dinargab\LibraryBot\Domain\Book\ValueObject\BookSuggestionStatus;

class ListSuggestionsUseCase
{
    public function __construct(
        private BookSuggestionRepositoryInterface $bookSuggestionRepository,
    )
    {
    }

    public function __invoke(
        ListSuggestionRequestDTO $bookSuggestionRequestDTO,
    ): ListSuggestionResponseDTO
    {

        if ($bookSuggestionRequestDTO->userId) {
            $suggestions = $this->bookSuggestionRepository->findByUser($bookSuggestionRequestDTO->userId, $bookSuggestionRequestDTO->page, $bookSuggestionRequestDTO->limit);
            $totalItems = $this->bookSuggestionRepository->count(['userId' => $bookSuggestionRequestDTO->userId]);
        } else {
            $suggestions = $this->bookSuggestionRepository->findPending($bookSuggestionRequestDTO->page, $bookSuggestionRequestDTO->limit);
            $totalItems = $this->bookSuggestionRepository->count(["status" => BookSuggestionStatus::PENDING]);
        }
        $maxPage = (int) ceil($totalItems / $bookSuggestionRequestDTO->limit);

        return new ListSuggestionResponseDTO(
            array_map(fn(BookSuggestion $suggestion) => SuggestionDTO::fromEntity($suggestion), $suggestions),
            $bookSuggestionRequestDTO->page,
            maxPage: $maxPage === 0 ? 1 : $maxPage,
        );
    }
}
