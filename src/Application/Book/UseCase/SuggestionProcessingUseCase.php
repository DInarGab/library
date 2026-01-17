<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Application\Book\UseCase;

use Dinargab\LibraryBot\Application\Book\DTO\SuggestionProcessingRequestDTO;
use Dinargab\LibraryBot\Application\Shared\DTO\SuggestionDTO;
use Dinargab\LibraryBot\Domain\Book\Repository\BookSuggestionRepositoryInterface;
use Dinargab\LibraryBot\Domain\Book\ValueObject\BookSuggestionStatus;
use InvalidArgumentException;

class SuggestionProcessingUseCase
{
    public function __construct(
        private BookSuggestionRepositoryInterface $bookSuggestionRepository,
    )
    {

    }

    public function __invoke(
        SuggestionProcessingRequestDTO $suggestionRequestDTO
    )
    {
        return match ($suggestionRequestDTO->suggestionStatus) {
            BookSuggestionStatus::REJECTED, BookSuggestionStatus::APPROVED => $this->processSuggestion($suggestionRequestDTO),
            default => throw new InvalidArgumentException('Invalid suggestion status')
        };
    }

    private function processSuggestion(SuggestionProcessingRequestDTO $suggestionRequestDTO)
    {
        $suggestion = $this->bookSuggestionRepository->findById($suggestionRequestDTO->suggestionId);
        $suggestion->setStatus($suggestionRequestDTO->suggestionStatus);
        if ($suggestionRequestDTO->adminComment) {
            $suggestion->setAdminComment($suggestionRequestDTO->adminComment);
        }
        $this->bookSuggestionRepository->save($suggestion);
        return SuggestionDTO::fromEntity($suggestion);
    }
}
