<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Infrastructure\Persistence\Repository;

use Dinargab\LibraryBot\Domain\Book\Entity\BookSuggestion;
use Dinargab\LibraryBot\Domain\Book\Repository\BookSuggestionRepositoryInterface;
use Dinargab\LibraryBot\Domain\Book\ValueObject\BookSuggestionStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

class BookSuggestionRepository extends ServiceEntityRepository implements BookSuggestionRepositoryInterface
{

    public function __construct(
        ManagerRegistry                $registry,
        private EntityManagerInterface $entityManager
    )
    {
        parent::__construct($registry, BookSuggestion::class);
    }


    public function findById(string $id): ?BookSuggestion
    {
        return $this->findOneBy(['id' => $id]);
    }

    public function findPending(int $page, int $limit): array
    {
        return $this->createQueryBuilder('bookSuggestion')
            ->where('bookSuggestion.status = :status')
            ->setParameter('bookSuggestion.status', BookSuggestionStatus::PENDING)
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByUser(int $userId): array
    {
        // TODO: Implement findByUser() method.
    }

    public function findAll(): array
    {
        // TODO: Implement findAll() method.
    }

    public function save(BookSuggestion $suggestion): void
    {
        $this->entityManager->persist($suggestion);
        $this->entityManager->flush();
    }

    public function delete(BookSuggestion $suggestion): void
    {
        $this->entityManager->remove($suggestion);
        $this->entityManager->flush();
    }
}
