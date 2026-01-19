<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Application\Lending\UseCase;

use Dinargab\LibraryBot\Application\Shared\DTO\LendingDTO;
use Dinargab\LibraryBot\Domain\Event\EventDispatcherInterface;
use Dinargab\LibraryBot\Domain\Event\Events\LendingOverdueEvent;
use Dinargab\LibraryBot\Domain\Lending\Repository\LendingRepositoryInterface;

class GetOverdueLendingsUseCase
{
    public function __construct(
        private LendingRepositoryInterface $lendingRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function __invoke(): array
    {
        $overdueLendings = $this->lendingRepository->findOverdue();
        foreach ($overdueLendings as $lending) {
            $lending->markAsOverdue();
            $this->lendingRepository->save($lending);

            $this->eventDispatcher->dispatch(new LendingOverdueEvent(
                lendingId: $lending->getId(),
                bookId: $lending->getBookCopy()->getBook()->getId(),
                bookTitle: $lending->getBookCopy()->getBook()->getTitle(),
                userId: $lending->getUser()->getId(),
                userTelegramId: (string) $lending->getUser()->getTelegramId(),
                daysOverdue: abs($lending->getDaysUntilDue())
            ));
        }

        return array_map(
            fn($lending) => LendingDTO::fromEntity($lending),
            $overdueLendings
        );
    }
}
