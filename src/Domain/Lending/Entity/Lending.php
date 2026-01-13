<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Domain\Lending\Entity;

use DateTimeImmutable;
use Dinargab\LibraryBot\Domain\Book\Entity\BookCopy;
use Dinargab\LibraryBot\Domain\Lending\ValueObject\LendingStatus;
use Dinargab\LibraryBot\Domain\User\Entity\User;
use Doctrine\ORM\Mapping as ORM;

class Lending
{
    private const DEFAULT_LENDING_DAYS = 14;

    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private string $id;

    #[ORM\ManyToOne(targetEntity: BookCopy::class, inversedBy: 'lendings')]
    private BookCopy $bookCopy;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'lendings')]
    private User $user;

    #[ORM\Column(type: 'datetime_immutable', nullable: false, name: 'issued_at')]
    private DateTimeImmutable $issuedAt;
    #[ORM\Column(type: 'datetime_immutable', nullable: false, name: 'due_date')]
    private DateTimeImmutable $dueDate;
    #[ORM\Column(type: 'datetime_immutable', nullable: false, name: 'returned_at')]
    private ?DateTimeImmutable $returnedAt;
    #[ORM\Column(type: 'string', nullable: false, length: 20)]
    private LendingStatus $status;

    #[ORM\Column(type: 'datetime_immutable', name: "created_at", nullable: false)]

    private DateTimeImmutable $createdAt;

    public function __construct(
        BookCopy $bookCopy,
        User $user,
        ?DateTimeImmutable $dueDate = null
    ) {
        $this->bookCopy = $bookCopy;
        $this->user = $user;
        $this->issuedAt = new DateTimeImmutable();
        $this->dueDate = $dueDate ?? $this->issuedAt->modify('+' . self::DEFAULT_LENDING_DAYS . ' days');
        $this->returnedAt = null;
        $this->status = LendingStatus::ACTIVE;
        $this->createdAt = new DateTimeImmutable();

    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getBookCopy(): BookCopy
    {
        return $this->bookCopy;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getIssuedAt(): DateTimeImmutable
    {
        return $this->issuedAt;
    }

    public function getDueDate(): DateTimeImmutable
    {
        return $this->dueDate;
    }

    public function getReturnedAt(): ?DateTimeImmutable
    {
        return $this->returnedAt;
    }

    public function getStatus(): LendingStatus
    {
        return $this->status;
    }

    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    public function isOverdue(): bool
    {
        if ($this->returnedAt !== null) {
            return false;
        }

        return $this->dueDate < new DateTimeImmutable();
    }

    public function getDaysUntilDue(): int
    {
        $now = new DateTimeImmutable();
        $diff = $now->diff($this->dueDate);

        return $diff->invert ? -$diff->days : $diff->days;
    }

    public function return(): void
    {
        if (!$this->status->canBeReturned()) {
            throw new \DomainException('This lending cannot be returned');
        }

        $this->returnedAt = new DateTimeImmutable();
        $this->status = LendingStatus::RETURNED;

    }

    public function markAsOverdue(): void
    {
        if ($this->status !== LendingStatus::ACTIVE) {
            return;
        }

        $this->status = LendingStatus::OVERDUE;
    }

    public function extendDueDate(int $days): void
    {
        if (!$this->isActive()) {
            throw new \DomainException('Cannot extend inactive lending');
        }

        $this->dueDate = $this->dueDate->modify("+{$days} days");
    }

}
