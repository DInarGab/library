<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Infrastructure\Persistence\Repository;

use DateTimeImmutable;
use Dinargab\LibraryBot\Domain\Book\Entity\Book;
use Dinargab\LibraryBot\Domain\Book\Repository\BookRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

class BookRepository extends ServiceEntityRepository implements BookRepositoryInterface
{

    public function __construct(
        ManagerRegistry                $registry,
        private EntityManagerInterface $entityManager
    )
    {
        parent::__construct($registry, Book::class);
    }

    /**
     * @param int $id
     * @return Book|null
     */
    public function findById(int $id): ?Book
    {
        return $this->findOneBy(["id" => $id]);
//        return null;
    }

    public function findAll(int $page = 1, int $limit = 10): array
    {

        return $this->createQueryBuilder("book")
            ->select("book")
            ->orderBy("book.id", "DESC")
            ->setMaxResults($limit)
            ->setFirstResult($this->getOffset($page, $limit))
            ->getQuery()
            ->getResult();
    }

    public function getCount(): int
    {
        return $this->count();
    }

    public function findAvailable(int $page = 1, int $limit = 10): array
    {
        return $this->createQueryBuilder("book")
            ->select("book")
            ->join("book.copies", "copies")
            ->where('copies.status = :status')
            ->setParameter('status', 'available')
            ->orderBy("book.id", "DESC")
            ->setMaxResults($limit)
            ->setFirstResult($this->getOffset($page, $limit))
            ->getQuery()
            ->getResult();
    }

    public function findAddedAfter(DateTimeImmutable $date): array
    {
        // TODO: Implement findAddedAfter() method.
    }

    public function save(Book $book): void
    {
        $this->entityManager->persist($book);
        $this->entityManager->flush();
    }

    public function delete(Book $book): void
    {
        $this->entityManager->remove($book);
        $this->entityManager->flush();
    }

    public function search(string $query): array
    {
        // TODO: Implement search() method.
    }

    private function getOffset(int $page, int $limit): int
    {
        return $offset = ($page - 1) * $limit;
    }
}
