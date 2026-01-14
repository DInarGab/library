<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Infrastructure\Bot\Keyboard;

use SergiX44\Nutgram\Telegram\Types\Keyboard\KeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\ReplyKeyboardMarkup;

class MainKeyboard
{
    public static function create()
    {
        return ReplyKeyboardMarkup::make(resize_keyboard: true, one_time_keyboard: true)->addRow(
            KeyboardButton::make('Посмотреть все книги'),
            KeyboardButton::make('Посмотреть мои книги')
        )->addRow(
            KeyboardButton::make('Предложить книгу')
        );
    }

    public static function createAdminKeyboard()
    {
        return ReplyKeyboardMarkup::make(resize_keyboard: true, one_time_keyboard: true)->addRow(
            KeyboardButton::make('Посмотреть все книги'),
            KeyboardButton::make('Добавить книгу'),
        )->addRow(
            KeyboardButton::make('Выдать книгу'),
            KeyboardButton::make('Принять книгу'),
        )->addRow(
            KeyboardButton::make('Посмотреть предложения')
        );
    }
}
