<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Domain\Book\Entity;

use DateTimeImmutable;
use Dinargab\LibraryBot\Domain\Book\ValueObject\BookStatus;
use Dinargab\LibraryBot\Infrastructure\Persistence\Repository\BookCopyRepository;
use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity(repositoryClass: BookCopyRepository::class)]
class BookCopy
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private int $id;

    #[ORM\Column(type: 'string', length: 20, enumType: BookStatus::class)]
    private BookStatus $status;
    #[ORM\Column(type: 'datetime_immutable', name: 'created_at', nullable: false)]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'string', length: 50, nullable: false, name: "inventory_number", unique: true)]
    private string $inventoryNumber;
    #[ORM\ManyToOne(targetEntity: Book::class, inversedBy: 'copies')]
    #[ORM\JoinColumn(nullable: false)]
    private Book $book;

    public function __construct(
        Book $book,
        string $inventoryNumber,
    ) {
        $this->book = $book;
        $this->inventoryNumber = $inventoryNumber;
        $this->status = BookStatus::AVAILABLE;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getBook(): Book
    {
        return $this->book;
    }

    public function getInventoryNumber(): string
    {
        return $this->inventoryNumber;
    }

    public function getStatus(): BookStatus
    {
        return $this->status;
    }


    public function isAvailable(): bool
    {
        return $this->status->isAvailable();
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function markAsBorrowed()
    {
        $this->status = BookStatus::BORROWED;
    }

    public function markAsAvailable()
    {
        $this->status = BookStatus::AVAILABLE;
    }

}
