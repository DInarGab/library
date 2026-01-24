<?php

namespace Dinargab\LibraryBot\Tests\Factory\Book\Entity;

use Dinargab\LibraryBot\Domain\Book\Entity\BookSuggestion;
use Dinargab\LibraryBot\Domain\Book\ValueObject\BookSuggestionStatus;
use Dinargab\LibraryBot\Tests\Factory\User\Entity\UserFactory;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<BookSuggestion>
 */
final class BookSuggestionFactory extends PersistentObjectFactory
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
        return BookSuggestion::class;
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
            'status' => self::faker()->randomElement(BookSuggestionStatus::cases()),
            'title' => self::faker()->text(500),
            'isbn' => self::faker()->isbn10(),
            'comment' => self::faker()->randomElement([self::faker()->text(300), null]),
            'user' => UserFactory::new()->create(),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[\Override]
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(BookSuggestion $bookSuggestion): void {})
        ;
    }
}
