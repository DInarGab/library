<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Domain\User\Entity;

use DateTimeImmutable;
use Dinargab\LibraryBot\Domain\User\ValueObject\TelegramId;
use Dinargab\LibraryBot\Domain\User\ValueObject\UserRole;
use Dinargab\LibraryBot\Infrastructure\Persistence\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: "bot_user")]
#[ORM\Entity(repositoryClass: UserRepository::class)]
class User
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private int $id;

    #[ORM\Embedded(class: TelegramId::class, columnPrefix: "user_")]
    private TelegramId $telegramId;
    #[ORM\Column(type: 'string', length: 255)]
    private ?string $username;
    #[ORM\Column(type: 'string', length: 255, name: 'first_name', nullable: true)]
    private ?string $firstName;
    #[ORM\Column(type: 'string', length: 255, name: 'last_name', nullable: true)]
    private ?string $lastName;
    #[ORM\Column(type: 'string', length: 20, enumType: UserRole::class)]
    private UserRole $role;

    #[ORM\Column(type: 'datetime_immutable', name: "created_at", nullable: false)]
    private DateTimeImmutable $createdAt;

    public function __construct(
        TelegramId $telegramId,
        ?string $username = null,
        ?string $firstName = null,
        ?string $lastName = null,
        UserRole $role = UserRole::USER
    ) {
        $this->telegramId = $telegramId;
        $this->username = $username;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->role = $role;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTelegramId(): TelegramId
    {
        return $this->telegramId;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function getRole(): UserRole
    {
        return $this->role;
    }

    public function isAdmin(): bool
    {
        return $this->role->isAdmin();
    }

    public function getDisplayName(): string
    {
        if ($this->firstName) {
            $name = $this->firstName;
            if ($this->lastName) {
                $name .= ' ' . $this->lastName;
            }
            return $name;
        }

        return $this->username ?? "User #{$this->telegramId->getValue()}";
    }
}
