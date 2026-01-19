<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Application\Lending\UseCase;

use Dinargab\LibraryBot\Application\Lending\DTO\LendingRequestDTO;
use Dinargab\LibraryBot\Application\Shared\DTO\LendingDTO;
use Dinargab\LibraryBot\Domain\Book\Repository\BookCopyRepositoryInterface;
use Dinargab\LibraryBot\Domain\Book\Repository\BookRepositoryInterface;
use Dinargab\LibraryBot\Domain\Event\EventDispatcherInterface;
use Dinargab\LibraryBot\Domain\Event\Events\BookLentEvent;
use Dinargab\LibraryBot\Domain\Exception\BookNotAvailableException;
use Dinargab\LibraryBot\Domain\Exception\BookNotFoundException;
use Dinargab\LibraryBot\Domain\Exception\UserNotFoundException;
use Dinargab\LibraryBot\Domain\Lending\Factory\LendingFactoryInterface;
use Dinargab\LibraryBot\Domain\Lending\Repository\LendingRepositoryInterface;
use Dinargab\LibraryBot\Domain\User\Repository\UserRepositoryInterface;
use Dinargab\LibraryBot\Infrastructure\Persistence\Repository\BookCopyRepository;

class LendBookUseCase
{

    private const DEFAULT_LENDING_DAYS = 14;

    public function __construct(
        private BookRepositoryInterface $bookRepository,
        private UserRepositoryInterface $userRepository,
        private LendingRepositoryInterface $lendingRepository,
        private LendingFactoryInterface $lendingFactory,
        private BookCopyRepositoryInterface $bookCopyRepository,
        private EventDispatcherInterface $eventDispatcher
    ) {}

    public function __invoke(
        LendingRequestDTO $lendingRequestDTO,
    ): LendingDTO {
        $book = $this->bookRepository->findById($lendingRequestDTO->bookId);

        if ($book === null) {
            throw new BookNotFoundException("Book not found");
        }

        $user = $this->userRepository->findById($lendingRequestDTO->userId);

        if ($user === null) {
            throw new UserNotFoundException("User not found");
        }

        $availableCopy = $book->getFirstAvailableCopy();

        if ($availableCopy === null) {
            throw new BookNotAvailableException("No available copies of this book");
        }

        $lending = $this->lendingFactory->create(
            bookCopy: $availableCopy,
            user: $user,
            daysToReturn: $lendingRequestDTO->daysToReturn ?? self::DEFAULT_LENDING_DAYS
        );

        $this->lendingRepository->save($lending);

        $availableCopy->markAsBorrowed();
        $this->bookCopyRepository->save($availableCopy);

        $this->eventDispatcher->dispatch(new BookLentEvent(
            lendingId: $lending->getId(),
            bookId: $availableCopy->getBook()->getId(),
            bookAuthor: $availableCopy->getBook()->getAuthor(),
            bookTitle: $availableCopy->getBook()->getTitle(),
            bookCopyId: $availableCopy->getId(),
            inventoryNumber: $availableCopy->getInventoryNumber(),
            userTelegramId: $user->getTelegramId()->getValue(),
            dueDate: $lending->getDueDate()
        ));
        return LendingDTO::fromEntity($lending);
    }

}
