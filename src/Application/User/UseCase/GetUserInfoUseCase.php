<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Application\User\UseCase;

use Dinargab\LibraryBot\Application\Shared\DTO\UserDTO;
use Dinargab\LibraryBot\Application\User\DTO\GetUserInfoRequestDTO;
use Dinargab\LibraryBot\Domain\Exception\UserNotFoundException;
use Dinargab\LibraryBot\Infrastructure\Persistence\Repository\UserRepository;

class GetUserInfoUseCase
{
    public function __construct(
        private UserRepository $userRepository,
    )
    {

    }

    public function __invoke(
        GetUserInfoRequestDTO $getUserInfoRequestDTO,
    )
    {
        $userInfo = $this->userRepository->findById($getUserInfoRequestDTO->userId);
        if ($userInfo === null) {
            throw new UserNotFoundException();
        };
        return UserDTO::fromEntity($userInfo);
    }
}
