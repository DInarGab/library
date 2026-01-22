<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Domain\Lending\Factory;

use Dinargab\LibraryBot\Domain\Book\Entity\BookCopy;
use Dinargab\LibraryBot\Domain\Lending\Entity\Lending;
use Dinargab\LibraryBot\Domain\User\Entity\User;

interface LendingFactoryInterface
{
    public function create(BookCopy $bookCopy, User $user, ?int $daysToReturn): Lending;

}
