<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Infrastructure\Bot\Middlewares;

use Dinargab\LibraryBot\Application\Shared\DTO\UserDTO;
use SergiX44\Nutgram\Nutgram;

class AdminMiddleware
{
    public function __construct(
    )
    {

    }


    public function __invoke(Nutgram $bot, $next)
    {
        /** @var UserDTO|null $user */
        $user = $bot->get('user');
        if ($user === null || !$user->isAdmin) {
            $bot->sendMessage('Access denied');
            $bot->endConversation();
            return;
        }
        $next($bot);
    }
}
