<?php
declare(strict_types=1);


namespace Dinargab\LibraryBot\Domain\Book\Entity;

use DateTimeImmutable;
use Dinargab\LibraryBot\Domain\Book\ValueObject\BookSuggestionStatus;
use Dinargab\LibraryBot\Domain\User\Entity\User;
use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity(repositoryClass: BookSuggestionRepository::class)]
class BookSuggestion
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private int $id;

    #[ORM\Column(type: 'string', length: 20)]
    private BookSuggestionStatus $status;
    #[Orm\Column(type: 'text', name: 'admin_comment', nullable: true)]
    private ?string $adminComment;

    #[ORM\Column(type: 'datetime_immutable', name: "created_at", nullable: false)]
    private DateTimeImmutable $createdAt;
    #[ORM\ManyToOne(targetEntity: User::class)]
    private User $user;

    #[ORM\Column(type: 'string', length: 500)]
    private ?string $title = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $author = null;

    #[ORM\Column(type: 'string', length: 1000, nullable: true, name: "source_url")]
    private ?string $sourceUrl = null;

    private ?array $parsedData = null;

    public function __construct(
        User $user,
        ?string $title = null,
        ?string $author = null,
        ?string $sourceUrl = null,
        ?array $parsedData = null
    ) {
        $this->parsedData = $parsedData;
        $this->sourceUrl = $sourceUrl;
        $this->author = $author;
        $this->title = $title;
        $this->user = $user;
        $this->status = BookSuggestionStatus::PENDING;
        $this->adminComment = null;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getTitle(): ?string
    {
        return $this->title ?? $this->parsedData['title'] ?? null;
    }

    public function getAuthor(): ?string
    {
        return $this->author ?? $this->parsedData['author'] ?? null;
    }

    public function getSourceUrl(): ?string
    {
        return $this->sourceUrl;
    }

    public function getParsedData(): ?array
    {
        return $this->parsedData;
    }

    public function getStatus(): BookSuggestionStatus
    {
        return $this->status;
    }

    public function getAdminComment(): ?string
    {
        return $this->adminComment;
    }

    public function isPending(): bool
    {
        return $this->status === BookSuggestionStatus::PENDING;
    }


    public function getDisplayInfo(): string
    {
        $info = [];

        if ($this->getTitle()) {
            $info[] = $this->getTitle();
        }

        if ($this->getAuthor()) {
            $info[] = $this->getAuthor();
        }

        if ($this->sourceUrl) {
            $info[] = $this->sourceUrl;
        }

        return implode("\n", $info);
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
