<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Application\Book\UseCase;

use Dinargab\LibraryBot\Application\Book\DTO\AddBookRequestDTO;
use Dinargab\LibraryBot\Application\Shared\DTO\BookDTO;
use Dinargab\LibraryBot\Domain\Book\Entity\BookCopy;
use Dinargab\LibraryBot\Domain\Book\Factory\BookFactoryInterface;
use Dinargab\LibraryBot\Domain\Book\Repository\BookCopyRepositoryInterface;
use Dinargab\LibraryBot\Domain\Book\Repository\BookRepositoryInterface;
use Dinargab\LibraryBot\Domain\Event\EventDispatcherInterface;
use Dinargab\LibraryBot\Domain\Event\Events\BookAddedEvent;

class AddBookUseCase
{
    public function __construct(
        private BookRepositoryInterface $bookRepository,
        private BookCopyRepositoryInterface $bookCopyRepository,
        private BookFactoryInterface $bookFactory,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(
        AddBookRequestDTO $addBookRequestDTO,
    ): BookDTO {
        $book = $this->bookFactory->create(
            title: $addBookRequestDTO->title,
            author: $addBookRequestDTO->author,
            isbn: $addBookRequestDTO->isbn,
            description: $addBookRequestDTO->description,
            coverUrl: $addBookRequestDTO->coverUrl
        );

        $this->bookRepository->save($book);

        for ($i = 0; $i < $addBookRequestDTO->copies; $i++) {
            $inventoryNumber = $this->bookCopyRepository->generateInventoryNumber();
            $bookCopy        = new BookCopy($book, $inventoryNumber);
            $book->addCopy($bookCopy);
            $this->bookCopyRepository->save($bookCopy);
        }
        $this->eventDispatcher->dispatch(
            new BookAddedEvent(
                $book->getId(),
                $book->getTitle(),
                $book->getAuthor(),
                $book->getIsbn()->getValue(),
                $addBookRequestDTO->copies
            )
        );

        return BookDTO::fromEntity($book);
    }
}
