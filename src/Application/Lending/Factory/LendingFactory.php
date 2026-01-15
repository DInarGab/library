<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Application\Lending\Factory;

use Dinargab\LibraryBot\Domain\Book\Entity\BookCopy;
use Dinargab\LibraryBot\Domain\Lending\Entity\Lending;
use Dinargab\LibraryBot\Domain\Lending\Factory\LendingFactoryInterface;
use Dinargab\LibraryBot\Domain\User\Entity\User;

class LendingFactory implements LendingFactoryInterface
{

    public function create(BookCopy $bookCopy, User $user, ?int $daysToReturn): Lending
    {
        if ($daysToReturn === null) {
            $daysToReturn = 7;
        }
        $dueDate = (new \DateTimeImmutable('now'))->modify("+$daysToReturn days");

        return new Lending($bookCopy, $user, $dueDate);
    }
}
