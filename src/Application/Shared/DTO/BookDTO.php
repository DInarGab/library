<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Application\Shared\DTO;

use Dinargab\LibraryBot\Domain\Book\Entity\Book;

class BookDTO
{
    public function __construct(
        public int $id,
        public string $title,
        public string $author,
        public ?string $isbn,
        public ?string $description,
        public ?string $coverUrl,
        public int $availableCopies,
        public string $createdAt,
        public ?int $firstAvailableCopyId,
    ) {}

    public static function fromEntity(Book $book): self
    {
        return new self(
            id: $book->getId() ?? 0,
            title: $book->getTitle(),
            author: $book->getAuthor(),
            isbn: $book->getIsbn()->getValue(),
            description: $book->getDescription(),
            coverUrl: $book->getCoverUrl(),
            availableCopies: $book->getAvailableCopiesCount(),
            createdAt: $book->getCreatedAt()->format('Y-m-d H:i:s'),
            firstAvailableCopyId: $book->getFirstAvailableCopy()?->getId(),
        );
    }
}
