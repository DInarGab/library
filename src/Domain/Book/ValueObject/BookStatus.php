<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Domain\Book\ValueObject;

enum BookStatus: string
{
    case AVAILABLE = 'available';
    case BORROWED = 'borrowed';
    case LOST = 'lost';

    public function isAvailable(): bool
    {
        return $this === self::AVAILABLE;
    }

    public function canBeBorrowed(): bool
    {
        return $this === self::AVAILABLE;
    }
}
