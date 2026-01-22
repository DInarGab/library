<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Application\Lending\UseCase;

use Dinargab\LibraryBot\Application\Lending\DTO\ListLendingsRequestDTO;
use Dinargab\LibraryBot\Application\Lending\DTO\ListLendingsResponseDTO;
use Dinargab\LibraryBot\Application\Shared\DTO\LendingDTO;
use Dinargab\LibraryBot\Domain\Lending\Entity\Lending;
use Dinargab\LibraryBot\Domain\Lending\Repository\LendingRepositoryInterface;

class ListLendingsUseCase
{
    public function __construct(
        private LendingRepositoryInterface $lendingRepository
    ) {
    }

    public function __invoke(ListLendingsRequestDTO $getLendingsRequestDTO)
    {
        if (is_null($getLendingsRequestDTO->userId)) {
            $lendingsArray = $this->lendingRepository->findAll(
                $getLendingsRequestDTO->page,
                $getLendingsRequestDTO->limit
            );
            $lendingsCount = $this->lendingRepository->countAll();
        } else {
            $lendingsArray = $this->lendingRepository->findAllByUser($getLendingsRequestDTO->userId);
            $lendingsCount = $this->lendingRepository->countAll($getLendingsRequestDTO->userId);
        }
        $maxPage = (int)ceil($lendingsCount / $getLendingsRequestDTO->limit);

        return new ListLendingsResponseDTO(
            array_map(fn(Lending $lending) => LendingDTO::fromEntity($lending), $lendingsArray),
            $getLendingsRequestDTO->page,
            $maxPage === 0 ? 1 : $maxPage,
        );
    }
}
