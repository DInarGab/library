<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Infrastructure\Bot\Middlewares;

use Dinargab\LibraryBot\Application\User\DTO\GetOrCreateUserRequestDTO;
use Dinargab\LibraryBot\Application\User\UseCase\GetOrCreateUserUseCase;
use SergiX44\Nutgram\Nutgram;

class UserAuthMiddleware
{
    public function __construct(
        private GetOrCreateUserUseCase $getOrCreateUserUseCase
    ) {
    }

    public function __invoke(Nutgram $bot, $next)
    {
        $user    = $bot->user();
        $botUser = ($this->getOrCreateUserUseCase)(
            new GetOrCreateUserRequestDTO(
                (string)$user->id,
                $user->username,
                $user->first_name,
                $user->last_name,
                trim(getenv('TELEGRAM_ADMIN_ID')) === (string)$user->id
            )
        );

        $bot->set('user', $botUser);
        $next($bot);
    }
}
