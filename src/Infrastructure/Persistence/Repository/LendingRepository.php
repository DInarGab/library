<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Infrastructure\Persistence\Repository;

use DateTimeImmutable;
use Dinargab\LibraryBot\Domain\Book\Entity\BookCopy;
use Dinargab\LibraryBot\Domain\Lending\Entity\Lending;
use Dinargab\LibraryBot\Domain\Lending\Repository\LendingRepositoryInterface;
use Dinargab\LibraryBot\Domain\User\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class LendingRepository extends ServiceEntityRepository implements LendingRepositoryInterface
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Lending::class);
    }

    public function findById(int $id): ?Lending
    {
        return $this->find($id);
    }

    public function findActiveByUser(User $user): array
    {
        return $this->createQueryBuilder('lending')
            ->andWhere('lending.user = :user')
            ->andWhere('lending.status IN (:activeStatuses)')
            ->setParameter('user', $user)
            ->setParameter('activeStatuses', ['active', 'overdue'])
            ->orderBy('lending.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByUser(User $user): array
    {
        return $this->findBy(['user' => $user], ['issuedAt' => 'DESC']);
    }

    public function findActiveByBookCopy(BookCopy $bookCopy): ?Lending
    {
        return $this->createQueryBuilder('lending')
            ->andWhere('lending.bookCopy = :bookCopy')
            ->andWhere('lending.status IN (:activeStatuses)')
            ->setParameter('bookCopy', $bookCopy)
            ->setParameter('activeStatuses', ['active', 'overdue'])
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOverdue(): array
    {
        $now = new DateTimeImmutable();

        return $this->createQueryBuilder('lending')
            ->andWhere('lending.dueDate < :now')
            ->andWhere('lending.returnedAt IS NULL')
            ->andWhere('lending.status != :returned')
            ->setParameter('now', $now)
            ->setParameter('returned', 'returned')
            ->orderBy('lending.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findDueSoon(int $days = 3): array
    {
        $now = new DateTimeImmutable();
        $futureDate = $now->modify("+{$days} days");

        return $this->createQueryBuilder('lending')
            ->andWhere('lending.dueDate BETWEEN :now AND :future')
            ->andWhere('lending.returnedAt IS NULL')
            ->andWhere('lending.status = :status')
            ->setParameter('now', $now)
            ->setParameter('future', $futureDate)
            ->setParameter('status', 'borrowed')
            ->orderBy('lending.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findNeedingReminder(): array
    {
        $now = new DateTimeImmutable();
        $tomorrow = $now->modify('+1 day');

        return $this->createQueryBuilder('lending')
            ->andWhere('lending.dueDate BETWEEN :now AND :tomorrow')
            ->andWhere('lending.returnedAt IS NULL')
            ->andWhere('lending.status = :status')
            ->setParameter('now', $now)
            ->setParameter('tomorrow', $tomorrow)
            ->setParameter('status', 'borrowed')
            ->getQuery()
            ->getResult();
    }

    public function findAll(int $page = 1, int $limit = 10): array
    {
        $queryBuilder = $this->createQueryBuilder('lending')
            ->setMaxResults($limit)
            ->setFirstResult(($page - 1) * $limit);
        return $queryBuilder
            ->getQuery()
            ->getResult();
    }

    public function findAllByUser(int $userId, int $page = 1, int $limit = 10): array
    {
        return $this->createQueryBuilder('lending')
            ->andWhere('lending.user = :userId')
            ->setParameter('userId', $userId)
            ->setMaxResults($limit)
            ->setFirstResult(($page - 1) * $limit)
            ->getQuery()
            ->getResult();
    }

    public function findAllLended(int $page = 1, int $limit = 10): array
    {
        $queryBuilder = $this->createQueryBuilder('lending')
            ->where('lending.status =:status')
            ->setParameter('status', 'lent')
            ->setMaxResults($limit)
            ->setFirstResult(($page - 1) * $limit);
        return $queryBuilder
            ->getQuery()
            ->getResult();
    }

    public function save(Lending $lending): void
    {
        $this->getEntityManager()->persist($lending);
        $this->getEntityManager()->flush();
    }

    public function remove(Lending $lending): void
    {
        $this->getEntityManager()->remove($lending);
        $this->getEntityManager()->flush();
    }

    public function countAll($userId = null): int
    {
        if (is_null($userId)) {
            return $this->count([]);
        }
        return $this->count(['lending.user' => $userId]);
    }
}
