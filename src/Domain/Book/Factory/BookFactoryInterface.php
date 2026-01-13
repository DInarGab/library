<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Domain\Book\Factory;

use Dinargab\LibraryBot\Domain\Book\Entity\Book;

interface BookFactoryInterface
{
    public function create(string $title, string $author, ?string $isbn = null, ?string $description = null, ?string $coverUrl = null): Book;
}
