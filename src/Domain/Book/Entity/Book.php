<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Domain\Book\Entity;

use DateTimeImmutable;
use Dinargab\LibraryBot\Domain\Book\ValueObject\ISBN;
use Dinargab\LibraryBot\Domain\Event\EventSubjectInterface;
use Dinargab\LibraryBot\Infrastructure\Persistence\Repository\BookRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity(repositoryClass: BookRepository::class)]
class Book
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private int $id;
    #[ORM\Column(type: 'string', length: 500)]
    private string $title;
    #[ORM\Column(type: 'string', length: 255)]
    private string $author;
    #[ORM\Embedded(class: ISBN::class, columnPrefix: "book_")]
    private ?ISBN $isbn = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;
    #[ORM\Column(type: 'string', length: 1000, nullable: true, name: "cover_url")]
    private ?string $coverUrl = null;
    #[ORM\Column(type: 'datetime_immutable', name: "created_at", nullable: false)]
    private DateTimeImmutable $createdAt;

    /** @var Collection<int, BookCopy> */
    #[ORM\OneToMany(mappedBy: 'book', targetEntity: BookCopy::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $copies;

    public function __construct(
        string $title,
        string $author,
        ?string $isbn = null,
        ?string $description = null,
        ?string $coverUrl = null
    ) {
        $this->title       = $title;
        $this->author      = $author;
        $this->isbn        = $isbn ? new ISBN($isbn) : null;
        $this->description = $description;
        $this->coverUrl    = $coverUrl;
        $this->createdAt   = new DateTimeImmutable();
        $this->copies      = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function getIsbn(): ?ISBN
    {
        return $this->isbn;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getCoverUrl(): ?string
    {
        return $this->coverUrl;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /** @return Collection<int, BookCopy> */
    public function getCopies(): Collection
    {
        return $this->copies;
    }

    public function addCopy(BookCopy $copy): void
    {
        if ( ! $this->copies->contains($copy)) {
            $this->copies->add($copy);
        }
    }

    public function removeCopy(BookCopy $copy): void
    {
        $this->copies->removeElement($copy);
    }

    public function getAvailableCopiesCount(): int
    {
        return $this->copies->filter(
            fn(BookCopy $copy) => $copy->isAvailable()
        )->count();
    }

    public function hasAvailableCopy(): bool
    {
        return $this->getAvailableCopiesCount() > 0;
    }

    public function getFirstAvailableCopy(): ?BookCopy
    {
        foreach ($this->copies as $copy) {
            if ($copy->isAvailable()) {
                return $copy;
            }
        }

        return null;
    }

    public function update(
        string $title,
        string $author,
        ?string $isbn = null,
        ?string $description = null,
        ?string $coverUrl = null
    ): void {
        $this->title       = $title;
        $this->author      = $author;
        $this->isbn        = $isbn ? new ISBN($isbn) : null;
        $this->description = $description;
        $this->coverUrl    = $coverUrl;
    }

    public function getDisplayName(): string
    {
        return sprintf('"%s" - %s', $this->title, $this->author);
    }
}
