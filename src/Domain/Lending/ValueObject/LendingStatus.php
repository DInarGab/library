<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Domain\Lending\ValueObject;

enum LendingStatus: string
{
    case AVAILABLE = 'available';
    case LENT = 'lent';
    case RETURNED = 'returned';
    case OVERDUE = 'overdue';


    public function isLent(): bool
    {
        return $this === self::LENT;
    }

    public function canBeReturned(): bool
    {
        return $this === self::ACTIVE || $this === self::OVERDUE;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::AVAILABLE => 'Доступна',
            self::LENT => 'Выдана',
            self::RETURNED => 'Возвращена',
            self::OVERDUE => 'Просрочена'
        };
    }

    public function isAvailable(): bool
    {
        return $this === self::AVAILABLE;
    }


}
