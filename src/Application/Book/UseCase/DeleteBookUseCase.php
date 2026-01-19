<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Application\Book\UseCase;

use Dinargab\LibraryBot\Application\Book\DTO\DeleteBookRequestDTO;
use Dinargab\LibraryBot\Domain\Book\Repository\BookRepositoryInterface;
use Dinargab\LibraryBot\Domain\Event\EventDispatcherInterface;
use Dinargab\LibraryBot\Domain\Event\Events\BookDeletedEvent;
use Dinargab\LibraryBot\Domain\Exception\BookNotFoundException;
use Dinargab\LibraryBot\Domain\Lending\Repository\LendingRepositoryInterface;
use DomainException;

class DeleteBookUseCase
{
    public function __construct(
        private BookRepositoryInterface    $bookRepository,
        private LendingRepositoryInterface $lendingRepository,
        private EventDispatcherInterface   $eventDispatcher
    )
    {
    }


    public function __invoke(
        DeleteBookRequestDTO $deleteBookRequestDTO
    ): void
    {
        $book = $this->bookRepository->findById($deleteBookRequestDTO->bookId);

        if ($book === null) {
            throw new BookNotFoundException("Книга с ID:$deleteBookRequestDTO->bookId не найдена");
        }

        foreach ($book->getCopies() as $copy) {
            $activeLending = $this->lendingRepository->findActiveByBookCopy($copy);
            if ($activeLending !== null) {
                throw new DomainException(
                    "Невозможно удалить книгу '{$book->getTitle()}': есть выданные копии"
                );
            }
        }

        $bookTitle = $book->getTitle();
        $bookAuthor = $book->getAuthor();
        $bookId = $book->getId();

        $this->bookRepository->delete($book);

        $this->eventDispatcher->dispatch(new BookDeletedEvent(
            bookId: $bookId,
            title: $bookTitle,
            author: $bookAuthor
        ));
    }
}
