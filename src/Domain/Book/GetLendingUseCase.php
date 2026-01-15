<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Domain\Book;

use Dinargab\LibraryBot\Application\Lending\DTO\GetLendingRequestDTO;
use Dinargab\LibraryBot\Application\Lending\UseCase\Lend;
use Dinargab\LibraryBot\Application\Shared\DTO\LendingDTO;
use Dinargab\LibraryBot\Domain\Exception\LendingNotFoundException;
use Dinargab\LibraryBot\Domain\Lending\Repository\LendingRepositoryInterface;

class GetLendingUseCase
{
    public function __construct(
        private LendingRepositoryInterface $lendingRepository,
    )
    {

    }

    public function __invoke(
        GetLendingRequestDTO $dto,
    )
    {
        $lending = $this->lendingRepository->findById($dto->lendingId);
        if ($lending === null) {
            throw new LendingNotFoundException("Lending not found");
        }

        return LendingDTO::fromEntity($lending);

    }
}
