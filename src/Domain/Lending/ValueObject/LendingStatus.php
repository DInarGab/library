<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Domain\Lending\ValueObject;

enum LendingStatus: string
{
    case ACTIVE = 'active';
    case RETURNED = 'returned';
    case OVERDUE = 'overdue';


    public function isActive(): bool
    {
        return $this === self::ACTIVE || $this === self::OVERDUE;
    }

    public function canBeReturned(): bool
    {
        return $this === self::ACTIVE || $this === self::OVERDUE;
    }
}
