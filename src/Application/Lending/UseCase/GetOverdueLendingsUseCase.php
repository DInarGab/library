<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Application\Lending\UseCase;

use Dinargab\LibraryBot\Application\Shared\DTO\LendingDTO;
use Dinargab\LibraryBot\Domain\Lending\Repository\LendingRepositoryInterface;

class GetOverdueLendingsUseCase
{
    public function __construct(
        private LendingRepositoryInterface $lendingRepository
    ) {}

    public function __invoke(): array
    {
        $overdueLendings = $this->lendingRepository->findOverdue();

        return array_map(
            fn($lending) => LendingDTO::fromEntity($lending),
            $overdueLendings
        );
    }
}