<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Domain\Book\Factory;

use Dinargab\LibraryBot\Domain\Book\Entity\BookSuggestion;

interface BookSuggestionFactoryInterface
{
    public function create(User $user, string $title, ?string $author): BookSuggestion;

    public function createWithUrl(User $user, string $sourceUrl): BookSuggestion;
}