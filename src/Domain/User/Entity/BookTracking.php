<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Domain\User\Entity;

use DateTimeImmutable;
use Dinargab\LibraryBot\Domain\Book\Entity\Book;

class BookTracking
{
    private string $id;
    private Book $book;
    private User $user;
    private bool $isActive;
    private ?DateTimeImmutable $notifiedAt;
    private DateTimeImmutable $createdAt;

    public function __construct(
        Book $book,
        User $user
    ) {
        $this->book = $book;
        $this->user = $user;
        $this->isActive = true;
        $this->notifiedAt = null;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getBook(): Book
    {
        return $this->book;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getNotifiedAt(): ?DateTimeImmutable
    {
        return $this->notifiedAt;
    }

    public function markAsNotified(): void
    {
        $this->notifiedAt = new DateTimeImmutable();
        $this->isActive = false;
    }

    public function deactivate(): void
    {
        $this->isActive = false;
    }

    public function reactivate(): void
    {
        $this->isActive = true;
        $this->notifiedAt = null;
    }
}
