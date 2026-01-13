<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Domain\User\ValueObject;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
readonly class TelegramId implements \Stringable
{
    #[ORM\Column(type: 'bigint', name: 'telegram_id', unique: true, nullable: false)]
    private int $value;

    public function __construct(
        int $value
    ) {
        $this->value = $value;
        if ($value <= 0) {
            throw new \InvalidArgumentException('Telegram ID must be positive');
        }
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
