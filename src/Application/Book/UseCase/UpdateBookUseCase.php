<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Application\Book\UseCase;

use Dinargab\LibraryBot\Application\Book\DTO\UpdateBookRequestDTO;
use Dinargab\LibraryBot\Application\Shared\DTO\BookDTO;
use Dinargab\LibraryBot\Domain\Book\Repository\BookRepositoryInterface;
use Dinargab\LibraryBot\Domain\Exception\BookNotFoundException;

class UpdateBookUseCase
{
    public function __construct(
        private BookRepositoryInterface $bookRepository
    ) {
    }

    public function __invoke(
        UpdateBookRequestDTO $updateBookRequestDTO
    ): BookDTO {
        $book = $this->bookRepository->findById($updateBookRequestDTO->bookId);

        if ($book === null) {
            throw new BookNotFoundException("Book with ID {$updateBookRequestDTO->bookId} not found");
        }

        $book->update(
            title: $updateBookRequestDTO->title,
            author: $updateBookRequestDTO->author,
            isbn: $updateBookRequestDTO->isbn,
            description: $updateBookRequestDTO->description,
            coverUrl: $updateBookRequestDTO->coverUrl
        );

        return BookDTO::fromEntity($book);
    }
}
