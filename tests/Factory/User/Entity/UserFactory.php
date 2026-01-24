<?php

namespace Dinargab\LibraryBot\Tests\Factory\User\Entity;

use Dinargab\LibraryBot\Domain\User\Entity\User;
use Dinargab\LibraryBot\Domain\User\ValueObject\UserRole;
use Dinargab\LibraryBot\Tests\Factory\User\ValueObject\TelegramIdFactory;
use Override;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<User>
 */
final class UserFactory extends PersistentObjectFactory
{
    public function __construct()
    {
    }

    #[Override]
    public static function class(): string
    {
        return User::class;
    }

    #[Override]
    protected function defaults(): array|callable
    {
        return [
            'username'   => self::faker()->userName(),
            'role'       => UserRole::USER,
            'telegramId' => TelegramIdFactory::new(),
            'firstName'  => self::faker()->firstName(),
            'lastName'   => self::faker()->lastName(),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[Override]
    protected function initialize(): static
    {
        return $this;
    }
}
