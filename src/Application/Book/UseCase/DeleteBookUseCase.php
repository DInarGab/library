<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Application\Book\UseCase;

use Dinargab\LibraryBot\Application\Book\DTO\DeleteBookRequestDTO;
use Dinargab\LibraryBot\Domain\Book\Repository\BookRepositoryInterface;
use Dinargab\LibraryBot\Domain\Exception\BookNotFoundException;
use Dinargab\LibraryBot\Domain\Lending\Repository\LendingRepositoryInterface;

class DeleteBookUseCase
{
    public function __construct(
        private BookRepositoryInterface $bookRepository,
        private LendingRepositoryInterface $lendingRepository
    ) {}


    public function __invoke(
        DeleteBookRequestDTO $deleteBookRequestDTO
    )
    {
        $book = $this->bookRepository->findById($deleteBookRequestDTO->bookId);

        if ($book === null) {
            throw new BookNotFoundException("Book with ID {$deleteBookRequestDTO->bookId} not found");
        }

        foreach ($book->getCopies() as $copy) {
            $activeLending = $this->lendingRepository->findActiveByBookCopy($copy);
            if ($activeLending !== null) {
                throw new \DomainException(
                    "Cannot delete book '{$book->getTitle()}': there are active lendings"
                );
            }
        }

        $this->bookRepository->delete($book);
    }
}