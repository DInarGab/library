<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Application\Lending\UseCase;

use Dinargab\LibraryBot\Application\Lending\DTO\ReturnBookRequestDTO;
use Dinargab\LibraryBot\Application\Shared\DTO\LendingDTO;
use Dinargab\LibraryBot\Domain\Lending\Repository\LendingRepositoryInterface;

class ReturnBookUseCase
{
    public function __construct(
        private LendingRepositoryInterface $lendingRepository,
//        private EventDispatcher $eventDispatcher
    ) {}

    public function __invoke(
        ReturnBookRequestDTO $returnBookRequestDTO,
    ): LendingDTO
    {
        $lending = $this->lendingRepository->findById($returnBookRequestDTO->lendingId);

        if ($lending === null) {
            throw new \DomainException("Lending not found");
        }

        $lending->return();

        $this->lendingRepository->save($lending);


        return LendingDTO::fromEntity($lending);
    }
}