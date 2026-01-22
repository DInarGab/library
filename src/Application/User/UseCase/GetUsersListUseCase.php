<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Application\User\UseCase;

use Dinargab\LibraryBot\Application\Shared\DTO\UserDTO;
use Dinargab\LibraryBot\Application\User\DTO\GetUsersListRequestDTO;
use Dinargab\LibraryBot\Application\User\DTO\GetUsersListResponseDTO;
use Dinargab\LibraryBot\Domain\User\Entity\User;
use Dinargab\LibraryBot\Infrastructure\Persistence\Repository\UserRepository;

class GetUsersListUseCase
{
    public function __construct(
        private UserRepository $userRepository,
    ) {
    }


    public function __invoke(
        GetUsersListRequestDTO $getUsersListRequestDTO,
    ): GetUsersListResponseDTO {
        $users      = $this->userRepository->findAll($getUsersListRequestDTO->page, $getUsersListRequestDTO->limit);
        $totalPages = (int)ceil($this->userRepository->getCount() / $getUsersListRequestDTO->limit);

        $usersList = array_map(
            fn(User $user) => new UserDTO(
                $user->getId(),
                $user->getDisplayName(),
                $user->getUsername(),
                $user->getTelegramId()->getValue(),
                $user->isAdmin(),
            ),
            $users
        );

        return new GetUsersListResponseDTO(
            $usersList,
            $getUsersListRequestDTO->page,
            $totalPages === 0 ? 0 : $totalPages,
        );
    }
}
