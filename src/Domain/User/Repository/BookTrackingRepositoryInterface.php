<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Domain\User\Repository;

use Dinargab\LibraryBot\Domain\Book\Entity\Book;
use Dinargab\LibraryBot\Domain\User\Entity\BookTracking;
use Dinargab\LibraryBot\Domain\User\Entity\User;

interface BookTrackingRepositoryInterface
{
    public function findById(string $id): ?BookTracking;

    public function findByBookAndUser(Book $book, User $user): ?BookTracking;

    /** @return BookTracking[] */
    public function findActiveByBook(Book $book): array;

    /** @return BookTracking[] */
    public function findActiveByUser(User $user): array;

    public function save(BookTracking $tracking): void;

    public function delete(BookTracking $tracking): void;
}