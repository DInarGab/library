<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Application\Book\Factory;

use Dinargab\LibraryBot\Domain\Book\Entity\Book;
use Dinargab\LibraryBot\Domain\Book\Factory\BookFactoryInterface;

class BookFactory implements BookFactoryInterface
{

    public function create(
        string $title,
        string $author,
        ?string $isbn = null,
        ?string $description = null,
        ?string $coverUrl = null
    ): Book {
        return new Book(
            title: $title,
            author: $author,
            isbn: $isbn,
            description: $description,
            coverUrl: $coverUrl
        );
    }

}
