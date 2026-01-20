<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Application\Lending\UseCase;

use Dinargab\LibraryBot\Application\Lending\DTO\NotifyBeforeDeadlineResponseDTO;
use Dinargab\LibraryBot\Application\Shared\DTO\LendingDTO;
use Dinargab\LibraryBot\Domain\Event\EventDispatcherInterface;
use Dinargab\LibraryBot\Domain\Event\Events\LendingDeadlineNearEvent;
use Dinargab\LibraryBot\Domain\Lending\Entity\Lending;
use Dinargab\LibraryBot\Domain\Lending\Repository\LendingRepositoryInterface;

class NotifyBeforeDeadlineUseCase
{

    private const DAYS_UNTIL_DEADLINE = 2;

    public function __construct(
        private LendingRepositoryInterface $lendingRepository,
        private EventDispatcherInterface   $eventDispatcher
    )
    {

    }


    public function __invoke(): ?NotifyBeforeDeadlineResponseDTO
    {
        $dueSoonLendings = $this->lendingRepository->findDueSoon(self::DAYS_UNTIL_DEADLINE);

        if ($dueSoonLendings === null) {
            return null;
        }

        $lendings = array_map(fn(Lending $lending) => LendingDTO::fromEntity($lending), $dueSoonLendings);
        foreach ($lendings as $dueSoonLending) {
            $this->eventDispatcher->dispatch(new LendingDeadlineNearEvent(
                $dueSoonLending->bookAuthor,
                $dueSoonLending->bookTitle,
                $dueSoonLending->userTelegramId,
                $dueSoonLending->daysUntilDue,
                $dueSoonLending->dueDate,
            ));
        }
        return new NotifyBeforeDeadlineResponseDTO($lendings);
    }
}
