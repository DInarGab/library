<?php

namespace Dinargab\LibraryBot\Tests\Factory\Book\Entity;

use Dinargab\LibraryBot\Domain\Book\Entity\Book;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Book>
 */
final class BookFactory extends PersistentObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct()
    {
    }

    #[\Override]
    public static function class(): string
    {
        return Book::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    #[\Override]
    protected function defaults(): array|callable
    {
        return [
            'author' => self::faker()->text(255),
            'title' => self::faker()->text(500),
            'isbn' => self::faker()->isbn13(),
            'description' => self::faker()->text(500),
            'coverUrl' => null,
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[\Override]
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Book $book): void {})
        ;
    }

    public function withCopies(int $count = 1, array $copyAttributes = [])
    {
        return $this->afterPersist(function (Book $book) use ($count, $copyAttributes) {
            // Создаем копии, явно указывая эту книгу как родителя
            BookCopyFactory::new($copyAttributes)
                           ->many($count)
                           ->create(['book' => $book]);
        });
    }
}
