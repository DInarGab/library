<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Application\User\UseCase;

use Dinargab\LibraryBot\Application\Shared\DTO\UserDTO;
use Dinargab\LibraryBot\Application\User\DTO\GetOrCreateUserRequestDTO;
use Dinargab\LibraryBot\Domain\User\Factory\UserFactoryInterface;
use Dinargab\LibraryBot\Domain\User\Repository\UserRepositoryInterface;

class GetOrCreateUserUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserFactoryInterface $userFactory,
    ) {
    }

    public function __invoke(
        GetOrCreateUserRequestDTO $getOrCreateUserDTO,
    ): UserDTO {
        $user = $this->userRepository->findByTelegramId($getOrCreateUserDTO->telegramId);

        if ($user === null) {
            $user = $this->userFactory->create(
                telegramId: $getOrCreateUserDTO->telegramId,
                username: $getOrCreateUserDTO->username,
                firstName: $getOrCreateUserDTO->firstName,
                lastName: $getOrCreateUserDTO->lastName,
                isAdmin: $getOrCreateUserDTO->isAdmin,
            );

            $this->userRepository->save($user);
        }

        return UserDTO::fromEntity($user);
    }
}
