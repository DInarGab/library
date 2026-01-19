<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Application\Shared\DTO;

use Dinargab\LibraryBot\Domain\Book\Entity\BookSuggestion;

class SuggestionDTO
{
    public function __construct(
        public int $id,
        public string $userName,
        public string $userTelegramId,
        public ?string $isbn,
        public ?string $title,
        public ?string $author,
        public ?string $sourceUrl,
        public string $status,
        public ?string $adminComment,
        public ?string $comment,
        public string $createdAt
    ) {}

    public static function fromEntity(BookSuggestion $suggestion): self
    {
        return new self(
            id: $suggestion->getId() ?? 0,
            isbn: $suggestion->getIsbn()?->getValue(),
            userName: $suggestion->getUser()->getDisplayName(),
            userTelegramId: $suggestion->getUser()->getTelegramId()->getValue(),
            title: $suggestion->getTitle(),
            author: $suggestion->getAuthor(),
            sourceUrl: $suggestion->getSourceUrl(),
            status: $suggestion->getStatus()->value,
            adminComment: $suggestion->getAdminComment(),
            comment: $suggestion->getComment(),
            createdAt: $suggestion->getCreatedAt()->format('Y-m-d H:i')
        );
    }

}
