<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Domain\Book\Repository;

use Dinargab\LibraryBot\Domain\Book\Entity\BookSuggestion;

interface BookSuggestionRepositoryInterface
{
    public function findById(int $id): ?BookSuggestion;

    /** @return BookSuggestion[] */
    public function findPending(int $page, int $limit): array;

    /** @return BookSuggestion[] */
    public function findByUser(int $userId, int $page, int $limit): array;

    /** @return BookSuggestion[] */
    public function findAll(): array;

    public function save(BookSuggestion $suggestion): void;

    public function delete(BookSuggestion $suggestion): void;
}
