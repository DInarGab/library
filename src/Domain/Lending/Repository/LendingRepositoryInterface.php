<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Domain\Lending\Repository;

use Dinargab\LibraryBot\Domain\Book\Entity\Book;
use Dinargab\LibraryBot\Domain\Lending\Entity\Lending;
use Dinargab\LibraryBot\Domain\User\Entity\User;

interface LendingRepositoryInterface
{
    public function findById(int $id): ?Lending;

    /** @return Lending[] */
    public function findActiveByUser(User $user): array;

    /** @return Lending[] */
    public function findByUser(User $user): array;

    public function findByBook(Book $book): array;

    /** @return Lending[] */
    public function findOverdue(): array;

    /** @return Lending[] */
    public function findDueSoon(int $days = 3): array;

    /** @return Lending[] */
    public function findNeedingReminder(): array;

    /** @return Lending[] */
    public function findAll(): array;

    /** @return Lending[] */
    public function findAllByUser(int $userId): array;

    public function save(Lending $lending): void;

    public function remove(Lending $lending): void;

    public function countAll(?int $userId): int;

}
