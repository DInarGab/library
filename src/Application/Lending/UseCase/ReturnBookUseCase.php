<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Application\Lending\UseCase;

use DateTime;
use Dinargab\LibraryBot\Application\Lending\DTO\ReturnBookRequestDTO;
use Dinargab\LibraryBot\Application\Shared\DTO\LendingDTO;
use Dinargab\LibraryBot\Domain\Event\EventDispatcherInterface;
use Dinargab\LibraryBot\Domain\Event\Events\BookReturnedEvent;
use Dinargab\LibraryBot\Domain\Lending\Repository\LendingRepositoryInterface;
use DomainException;

class ReturnBookUseCase
{
    public function __construct(
        private LendingRepositoryInterface $lendingRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(
        ReturnBookRequestDTO $returnBookRequestDTO,
    ): LendingDTO {
        $lending = $this->lendingRepository->findById($returnBookRequestDTO->lendingId);
        if ($lending === null) {
            throw new DomainException("Lending not found");
        }

        $bookCopy = $lending->getBookCopy();


        $lending->return();
        $bookCopy->markAsAvailable();

        $this->lendingRepository->save($lending);
        $lendingDTO = LendingDTO::fromEntity($lending);
        $this->eventDispatcher->dispatch(
            new BookReturnedEvent(
                $lending->getId(),
                $lending->getBookCopy()->getBook()->getAuthor(),
                $lending->getBookCopy()->getBook()->getTitle(),
                $lending->getUser()->getDisplayName(),
                $lending->getUser()->getTelegramId()->getValue(),
                $lending->getDueDate() < new DateTime()
            )
        );

        return $lendingDTO;
    }
}
