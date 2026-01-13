<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Domain\Book\Repository;

use Dinargab\LibraryBot\Domain\Book\Entity\Book;

interface BookRepositoryInterface
{
    public function findById(int $id): ?Book;

    /** @return Book[] */
    public function findAll(): array;

    /** @return Book[] */
    public function findAvailable(): array;

    /** @return Book[] */
    public function findAddedAfter(\DateTimeImmutable $date): array;

    public function save(Book $book): void;

    public function delete(Book $book): void;

    public function search(string $query): array;

}