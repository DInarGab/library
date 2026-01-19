<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Domain\Event\Events;

use Dinargab\LibraryBot\Domain\Book\Entity\Book;

class BookAddedEvent extends AbstractDomainEvent
{
    public function __construct(
        public readonly int $bookId,
        public readonly string $title,
        public readonly string $author,
        public readonly ?string $isbn,
        public readonly int $copiesCount
    ) {
        parent::__construct();
    }
}
