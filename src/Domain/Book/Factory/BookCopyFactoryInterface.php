<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Domain\Book\Factory;

use Dinargab\LibraryBot\Domain\Book\Entity\Book;
use Dinargab\LibraryBot\Domain\Book\Entity\BookCopy;

interface BookCopyFactoryInterface
{
    public function create(Book $book, ?string $inventoryNumber): BookCopy;
}