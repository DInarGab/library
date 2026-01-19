<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Infrastructure\Bot\Commands;

use Dinargab\LibraryBot\Application\User\DTO\GetOrCreateUserRequestDTO;
use Dinargab\LibraryBot\Application\User\UseCase\GetOrCreateUserUseCase;
use Dinargab\LibraryBot\Infrastructure\Bot\Keyboard\KeyboardFactory;
use SergiX44\Nutgram\Nutgram;

class StartCommand
{
    public function __construct(
        private GetOrCreateUserUseCase $getOrCreateUserUseCase,
    )
    {
    }

    public function __invoke(Nutgram $bot)
    {
        $user = $bot->user();
        $registeredUser = ($this->getOrCreateUserUseCase)(
            new GetOrCreateUserRequestDTO(
                (string) $user->id,
                $user->username,
                $user->first_name,
                $user->last_name,
                (string) $user->id === trim(getenv('TELEGRAM_ADMIN_ID'))
            )
        );

        $text = "👋 Привет, *{$registeredUser->displayName}*!\n\n";
        $text .= "Добро пожаловать в библиотечного бота! \n\n";

        $bot->sendMessage(
            text: $text,
            parse_mode: 'Markdown',
        );
    }
}
