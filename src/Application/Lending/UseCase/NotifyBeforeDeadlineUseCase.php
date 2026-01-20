<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Application\Lending\UseCase;

use Dinargab\LibraryBot\Application\Lending\DTO\NotifyBeforeDeadlineResponseDTO;
use Dinargab\LibraryBot\Domain\Event\EventDispatcherInterface;
use Dinargab\LibraryBot\Domain\Event\Events\LendingDeadlineNearEvent;
use Dinargab\LibraryBot\Domain\Lending\Repository\LendingRepositoryInterface;

class NotifyBeforeDeadlineUseCase
{

    private const DAYS_UNTIL_DEADLINE = 2;
    public function __construct(
        private LendingRepositoryInterface $lendingRepository,
        private EventDispatcherInterface $eventDispatcher
    )
    {

    }


    public function __invoke(): ?NotifyBeforeDeadlineResponseDTO
    {
        $dueSoonLendings = $this->lendingRepository->findDueSoon(self::DAYS_UNTIL_DEADLINE);

        if ($dueSoonLendings === null) {
            return null;
        }

        foreach ($dueSoonLendings as $dueSoonLending) {
            $book = $dueSoonLending->getBookCopy()->getBook();
            $this->eventDispatcher->dispatch(new LendingDeadlineNearEvent(
                $book->getAuthor(),
                $book->getTitle(),
                $dueSoonLending->getUser()->getTelegramId()->getValue(),
                $dueSoonLending->getDaysUntilDue(),
                $dueSoonLending->getDueDate(),
            ));
        }
        return new NotifyBeforeDeadlineResponseDTO($dueSoonLendings);
    }
}
