<?php

namespace Dinargab\LibraryBot\Tests\Factory\Lending\Entity;

use Dinargab\LibraryBot\Domain\Lending\Entity\Lending;
use Dinargab\LibraryBot\Domain\Lending\ValueObject\LendingStatus;
use Dinargab\LibraryBot\Tests\Factory\Book\Entity\BookCopyFactory;
use Dinargab\LibraryBot\Tests\Factory\User\Entity\UserFactory;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Lending>
 */
final class LendingFactory extends PersistentObjectFactory
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
        return Lending::class;
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
            'dueDate' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'bookCopy' => BookCopyFactory::new()->create(),
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
            // ->afterInstantiate(function(Lending $lending): void {})
        ;
    }
}
