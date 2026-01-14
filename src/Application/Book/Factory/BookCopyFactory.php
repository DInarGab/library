<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Application\Book\Factory;

use Dinargab\LibraryBot\Domain\Book\Entity\Book;
use Dinargab\LibraryBot\Domain\Book\Entity\BookCopy;
use Dinargab\LibraryBot\Domain\Book\Factory\BookCopyFactoryInterface;

class BookCopyFactory implements BookCopyFactoryInterface
{
    public function create(
        Book $book,
        ?string $inventoryNumber = null,
        string $condition = 'good'
    ): BookCopy
    {
        return new BookCopy(
            book: $book,
            inventoryNumber: $inventoryNumber,
        );
    }

}
