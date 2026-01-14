<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Infrastructure\Bot;

use Psr\Container\ContainerInterface;
use SergiX44\Nutgram\Configuration;
use SergiX44\Nutgram\Nutgram;

class BotFactory
{
    public function create(string $telegramToken)
    {
        return new Nutgram($telegramToken, new Configuration(clientTimeout: 30));
    }
}
