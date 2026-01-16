<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Infrastructure\Persistence\Repository;

use Dinargab\LibraryBot\Domain\Book\Entity\BookSuggestion;
use Dinargab\LibraryBot\Domain\Book\Repository\BookSuggestionRepositoryInterface;
use Dinargab\LibraryBot\Domain\User\Entity\User;

class BookSuggestionRepository implements BookSuggestionRepositoryInterface
{

    public function findById(string $id): ?BookSuggestion
    {
        // TODO: Implement findById() method.
    }

    public function findPending(): array
    {
        // TODO: Implement findPending() method.
    }

    public function findByUser(int $userId): array
    {
        // TODO: Implement findByUser() method.
    }

    public function findAll(): array
    {
        // TODO: Implement findAll() method.
    }

    public function save(BookSuggestion $suggestion): void
    {
        // TODO: Implement save() method.
    }

    public function delete(BookSuggestion $suggestion): void
    {
        // TODO: Implement delete() method.
    }
}
