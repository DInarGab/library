<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Application\Book\UseCase;

use Dinargab\LibraryBot\Application\Book\DTO\GetBookRequestDTO;
use Dinargab\LibraryBot\Application\Shared\DTO\BookDTO;
use Dinargab\LibraryBot\Domain\Book\Repository\BookRepositoryInterface;
use Dinargab\LibraryBot\Domain\Exception\BookNotFoundException;

class GetBookUseCase
{
    public function __construct(
        private BookRepositoryInterface $bookRepository
    ) {
    }

    public function __invoke(GetBookRequestDTO $bookRequestDTO): BookDTO
    {
        $book = $this->bookRepository->findById($bookRequestDTO->bookId);

        if ($book === null) {
            throw new BookNotFoundException("Book with ID {$bookRequestDTO->bookId} not found");
        }

        return BookDTO::fromEntity($book);
    }
}
