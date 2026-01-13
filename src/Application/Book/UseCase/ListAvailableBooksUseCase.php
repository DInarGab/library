<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Application\Book\UseCase;

use Dinargab\LibraryBot\Application\Shared\DTO\BookDTO;
use Dinargab\LibraryBot\Domain\Book\Repository\BookRepositoryInterface;

class ListAvailableBooksUseCase
{
    public function __construct(
        private BookRepositoryInterface $bookRepository
    ) {}

    public function __invoke(): array
    {
        $books = $this->bookRepository->findAvailable();

        return array_map(
            fn($book) => BookDTO::fromEntity($book),
            $books
        );
    }
}