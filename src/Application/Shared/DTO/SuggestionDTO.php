<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Application\Shared\DTO;

use Dinargab\LibraryBot\Domain\Book\Entity\BookSuggestion;

class SuggestionDTO
{
    public function __construct(
        public int $id,
        public string $userName,
        public int $userTelegramId,
        public ?string $title,
        public ?string $author,
        public ?string $sourceUrl,
        public ?array $parsedData,
        public string $status,
        public ?string $adminComment,
        public string $createdAt
    ) {}

    public static function fromEntity(BookSuggestion $suggestion): self
    {
        return new self(
            id: $suggestion->getId() ?? 0,
            userName: $suggestion->getUser()->getDisplayName(),
            userTelegramId: $suggestion->getUser()->getTelegramId()->getValue(),
            title: $suggestion->getTitle(),
            author: $suggestion->getAuthor(),
            sourceUrl: $suggestion->getSourceUrl(),
            parsedData: $suggestion->getParsedData(),
            status: $suggestion->getStatus()->value,
            adminComment: $suggestion->getAdminComment(),
            createdAt: $suggestion->getCreatedAt()->format('Y-m-d H:i')
        );
    }

}