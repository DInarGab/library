<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Domain\Book\Repository;

use Dinargab\LibraryBot\Domain\Book\Entity\Book;
use Dinargab\LibraryBot\Domain\Book\Entity\BookCopy;

interface BookCopyRepositoryInterface
{
    public function findById(int $id): ?BookCopy;

    public function findByInventoryNumber(string $inventoryNumber): ?BookCopy;

    /** @return BookCopy[] */
    public function findByBook(Book $book): array;

    /** @return BookCopy[] */
    public function findAvailableByBook(Book $book): array;

    public function save(BookCopy $copy): void;

    public function delete(BookCopy $copy): void;

    public function generateInventoryNumber(): string;

}
