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
    )
    {

    }

    public function __invoke(ListLendingsRequestDTO $getLendingsRequestDTO)
    {
        $lendingsArray = $this->lendingRepository->findAll($getLendingsRequestDTO->page, $getLendingsRequestDTO->limit, $getLendingsRequestDTO->userId);
        $lendingsCount = $this->lendingRepository->countAll();
        return new ListLendingsResponseDTO(
            array_map(fn(Lending $lending) => LendingDTO::fromEntity($lending), $lendingsArray) ,
            $getLendingsRequestDTO->page,
            (int)ceil($lendingsCount / $getLendingsRequestDTO->limit));
    }
}
