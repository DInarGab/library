<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Application\Book\UseCase;

use Dinargab\LibraryBot\Application\Book\DTO\SuggestBookRequestDTO;
use Dinargab\LibraryBot\Application\Shared\DTO\SuggestionDTO;
use Dinargab\LibraryBot\Domain\Book\Entity\BookSuggestion;
use Dinargab\LibraryBot\Domain\Book\Repository\BookSuggestionRepositoryInterface;
use Dinargab\LibraryBot\Domain\Event\EventDispatcherInterface;
use Dinargab\LibraryBot\Domain\Event\Events\SuggestionAddedEvent;
use Dinargab\LibraryBot\Domain\Exception\UserNotFoundException;
use Dinargab\LibraryBot\Domain\User\Repository\UserRepositoryInterface;

class SuggestBookUseCase
{

    public function __construct(
        private BookSuggestionRepositoryInterface $suggestionRepository,
        private UserRepositoryInterface $userRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(
        SuggestBookRequestDTO $suggestBookRequestDTO
    ) {
        $user = $this->userRepository->findById($suggestBookRequestDTO->userId);

        if ($user === null) {
            throw new UserNotFoundException("User not found");
        }


        $suggestion = new BookSuggestion(
            user: $user,
            title: $suggestBookRequestDTO->title,
            author: $suggestBookRequestDTO->author,
            isbn: $suggestBookRequestDTO->isbn,
            comment: $suggestBookRequestDTO->comment,
            sourceUrl: $suggestBookRequestDTO->url,
        );

        $this->suggestionRepository->save($suggestion);
        $this->eventDispatcher->dispatch(
            new SuggestionAddedEvent(
                $suggestion->getAuthor(),
                $suggestion->getTitle(),
                $suggestion->getUser()->getDisplayName(),
                $suggestion->getComment(),
                $suggestion->getSourceUrl()
            )
        );

        return SuggestionDTO::fromEntity($suggestion);
    }
}
