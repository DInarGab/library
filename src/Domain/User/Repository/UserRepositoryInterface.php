<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Domain\User\Repository;

use Dinargab\LibraryBot\Domain\User\Entity\User;

interface UserRepositoryInterface
{
    public function findById(int $id): ?User;

    public function findByTelegramId(int $telegramId): ?User;

    public function findAll(int $page = 1, int $limit = 10): array;

    public function save(User $user): void;

    public function delete(User $user): void;
}
