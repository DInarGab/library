<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Infrastructure\Bot\Middlewares;

use Dinargab\LibraryBot\Domain\User\Entity\User;
use SergiX44\Nutgram\Nutgram;

class AdminMiddleware
{
    public function __construct(
    )
    {

    }


    public function __invoke(Nutgram $bot, $next)
    {
        /** @var User|null $user */
        $user = $bot->get('user');
        if ($user === null || !$user->isAdmin()) {
            $bot->sendMessage('Access denied');
        }
        $next($bot);
    }
}
