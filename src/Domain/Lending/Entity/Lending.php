<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Domain\Lending\Entity;

use DateTimeImmutable;
use Dinargab\LibraryBot\Domain\Book\Entity\BookCopy;
use Dinargab\LibraryBot\Domain\Lending\ValueObject\LendingStatus;
use Dinargab\LibraryBot\Domain\User\Entity\User;
use Dinargab\LibraryBot\Infrastructure\Persistence\Repository\LendingRepository;
use Doctrine\ORM\Mapping as ORM;
use DomainException;

#[ORM\Entity(repositoryClass: LendingRepository::class)]
class Lending
{
    private const DEFAULT_LENDING_DAYS = 14;

    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: BookCopy::class)]
    private BookCopy $bookCopy;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private User $user;

    #[ORM\Column(type: 'datetime_immutable', nullable: false, name: 'issued_at')]
    private DateTimeImmutable $issuedAt;
    #[ORM\Column(type: 'datetime_immutable', nullable: false, name: 'due_date')]
    private DateTimeImmutable $dueDate;
    #[ORM\Column(type: 'datetime_immutable', nullable: true, name: 'returned_at')]
    private ?DateTimeImmutable $returnedAt;
    #[ORM\Column(type: 'string', nullable: false, length: 20, enumType: LendingStatus::class)]
    private LendingStatus $status;

    #[ORM\Column(type: 'datetime_immutable', name: "created_at", nullable: false)]
    private DateTimeImmutable $createdAt;

    public function __construct(
        BookCopy $bookCopy,
        User $user,
        ?DateTimeImmutable $dueDate = null
    ) {
        $this->bookCopy   = $bookCopy;
        $this->user       = $user;
        $this->issuedAt   = new DateTimeImmutable();
        $this->dueDate    = $dueDate ?? $this->issuedAt->modify('+' . self::DEFAULT_LENDING_DAYS . ' days');
        $this->returnedAt = null;
        $this->status     = LendingStatus::LENT;
        $this->createdAt  = new DateTimeImmutable();
    }

    public function getId(): int
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
        return $this->status->isLent();
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
        $now  = new DateTimeImmutable();
        $diff = $now->diff($this->dueDate);

        return $diff->invert ? -$diff->days : $diff->days;
    }

    public function return(): void
    {
        if ( ! $this->status->canBeReturned()) {
            throw new DomainException('This lending cannot be returned');
        }

        $this->returnedAt = new DateTimeImmutable();
        $this->status     = LendingStatus::RETURNED;
    }

    public function markAsOverdue(): void
    {
        if ($this->status !== LendingStatus::RETURNED) {
            return;
        }

        $this->status = LendingStatus::OVERDUE;
    }

    public function setStatus(LendingStatus $status): Lending
    {
        $this->status = $status;

        return $this;
    }
}
