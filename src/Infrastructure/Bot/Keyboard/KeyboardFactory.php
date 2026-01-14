<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Infrastructure\Bot\Keyboard;

class KeyboardFactory
{
    public function create(KeyboardTypeEnum $type)
    {
        return match ($type) {
            KeyboardTypeEnum::ADMIN => MainKeyboard::createAdminKeyboard(),
            KeyboardTypeEnum::COMMON => MainKeyboard::create(),
        };
    }
}
