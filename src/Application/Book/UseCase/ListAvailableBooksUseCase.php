<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Application\Book\UseCase;

use Dinargab\LibraryBot\Application\Book\DTO\ListBooksRequestDTO;
use Dinargab\LibraryBot\Application\Book\DTO\ListBooksResponseDTO;
use Dinargab\LibraryBot\Application\Shared\DTO\BookDTO;
use Dinargab\LibraryBot\Domain\Book\Repository\BookRepositoryInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;

class ListAvailableBooksUseCase
{
    public function __construct(
        private BookRepositoryInterface $bookRepository
    ) {}

    public function __invoke(ListBooksRequestDTO $booksRequestDTO): ListBooksResponseDTO
    {
        $books = $this->bookRepository->findAll($booksRequestDTO->page, $booksRequestDTO->limit);
        $totalItems = $this->bookRepository->getCount();
        $maxPage = (int) ceil($totalItems / $booksRequestDTO->limit);

        return new ListBooksResponseDTO(
            books: array_map(
                fn($book) => BookDTO::fromEntity($book),
                $books
            ),
            page: $booksRequestDTO->page,
            maxPage: $maxPage === 0 ? 1 : $maxPage,
        );
    }
}
