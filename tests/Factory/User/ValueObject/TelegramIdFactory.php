<?php

namespace Dinargab\LibraryBot\Tests\Factory\User\ValueObject;

use Dinargab\LibraryBot\Domain\User\ValueObject\TelegramId;
use Zenstruck\Foundry\ObjectFactory;

/**
 * @extends ObjectFactory<TelegramId>
 */
final class TelegramIdFactory extends ObjectFactory
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
        return TelegramId::class;
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
            'value' => (string) self::faker()->numberBetween(10000000000, 99999999999),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[\Override]
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(TelegramId $telegramId): void {})
        ;
    }
}
