<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Application\Shared\DTO;

use Dinargab\LibraryBot\Domain\Lending\Entity\Lending;

class LendingDTO
{
    public function __construct(
        public int $id,
        public string $bookTitle,
        public string $bookAuthor,
        public string $userName,
        public string $userTelegramId,
        public string $issuedAt,
        public string $dueDate,
        public ?string $returnedAt,
        public string $status,
        public int $daysUntilDue,
        public bool $isOverdue,
        public string $statusDisplayValue,
    ) {}

    public static function fromEntity(Lending $lending): self
    {
        return new self(
            id: $lending->getId(),
            bookTitle: $lending->getBookCopy()->getBook()->getTitle(),
            bookAuthor: $lending->getBookCopy()->getBook()->getAuthor(),
            userName: $lending->getUser()->getDisplayName(),
            userTelegramId: $lending->getUser()->getTelegramId()->getValue(),
            issuedAt: $lending->getIssuedAt()->format('Y-m-d'),
            dueDate: $lending->getDueDate()->format('Y-m-d'),
            returnedAt: $lending->getReturnedAt()?->format('Y-m-d'),
            status: $lending->getStatus()->value,
            statusDisplayValue: $lending->getStatus()->getLabel(),
            daysUntilDue: $lending->getDaysUntilDue(),
            isOverdue: $lending->isOverdue()
        );
    }
}
