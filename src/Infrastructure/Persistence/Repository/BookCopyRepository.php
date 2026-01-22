<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Infrastructure\Persistence\Repository;

use Dinargab\LibraryBot\Domain\Book\Entity\Book;
use Dinargab\LibraryBot\Domain\Book\Entity\BookCopy;
use Dinargab\LibraryBot\Domain\Book\Repository\BookCopyRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BookCopyRepository extends ServiceEntityRepository implements BookCopyRepositoryInterface
{
    private static int $inventoryCounter = 0;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BookCopy::class);
    }

    public function findById(int $id): ?BookCopy
    {
        return $this->find($id);
    }

    public function findByInventoryNumber(string $inventoryNumber): ?BookCopy
    {
        return $this->findOneBy(['inventoryNumber' => $inventoryNumber]);
    }

    public function findByBook(Book $book): array
    {
        return $this->findBy(['book' => $book], ['inventoryNumber' => 'ASC']);
    }

    public function findAvailableByBook(Book $book): array
    {
        return $this->createQueryBuilder('copy')
                    ->andWhere('copy.book = :book')
                    ->andWhere('copy.status = :status')
                    ->setParameter('book', $book)
                    ->setParameter('status', 'available')
                    ->getQuery()
                    ->getResult();
    }

    public function save(BookCopy $copy): void
    {
        $this->getEntityManager()->persist($copy);
        $this->getEntityManager()->flush();
    }

    public function delete(BookCopy $copy): void
    {
        $this->getEntityManager()->remove($copy);
        $this->getEntityManager()->flush();
    }

    public function generateInventoryNumber(): string
    {
        self::$inventoryCounter++;
        $date   = date('Ymd');
        $random = str_pad((string)mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);

        return "LIB-{$date}-{$random}-" . self::$inventoryCounter;
    }
}
