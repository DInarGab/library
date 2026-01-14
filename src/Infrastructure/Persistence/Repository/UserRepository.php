<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Infrastructure\Persistence\Repository;

use Dinargab\LibraryBot\Domain\User\Entity\User;
use Dinargab\LibraryBot\Domain\User\Repository\UserRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends ServiceEntityRepository implements UserRepositoryInterface
{

    public function __construct(
        ManagerRegistry $registry,
    )
    {
        parent::__construct($registry, User::class);
    }

    public function findById(int $id): ?User
    {
        return $this->find($id);
    }

    public function findByTelegramId(int $telegramId): ?User
    {
        return $this->findOneBy(['telegramId.value' => $telegramId]);
    }

    public function findAll(): array
    {
        return $this->findBy([], ['createdAt' => 'ASC']);
    }

    public function save(User $user): void
    {
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function delete(User $user): void
    {
        $this->getEntityManager()->remove($user);
        $this->getEntityManager()->flush();
    }

}
