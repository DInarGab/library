<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Tests\Helper;

use PHPUnit\Framework\Assert;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

trait PaginationAssertTrait
{
    protected function assertPagination(
        InlineKeyboardMarkup|array $markup,
        int $currentPage,
        int $totalPages,
        string $commandPrefix
    ): void {
        $keyboard = $markup instanceof InlineKeyboardMarkup ? $markup->inline_keyboard : $markup['inline_keyboard'];

        $closeRow = $keyboard[count($keyboard) - 1];
        $navRow = $keyboard[count($keyboard) - 2];

        Assert::assertEquals('❌ Закрыть', $closeRow[0]['text'] ?? $closeRow[0]->text);
        Assert::assertEquals('close', $closeRow[0]['callback_data'] ?? $closeRow[0]->callback_data);


        // Строка навигации
        $foundCurrent = false;
        $foundNext = false;
        $foundPrev = false;

        foreach ($navRow as $btn) {
            $text = $btn['text'] ?? $btn->text;
            $data = $btn['callback_data'] ?? $btn->callback_data;

            if ($data === 'current') {
                $foundCurrent = true;
                Assert::assertEquals("Страница {$currentPage}/{$totalPages}", $text);
            }

            // Кнопка "Вперед"
            if (str_starts_with($data, $commandPrefix)) {
                // Парсим "list_books:2" -> page 2
                $targetPage = (int) explode(':', $data)[1];

                if ($targetPage > $currentPage) {
                    $foundNext = true;
                    Assert::assertStringContainsString('▶️', $text);
                } elseif ($targetPage < $currentPage) {
                    $foundPrev = true;
                    Assert::assertStringContainsString('◀️', $text);
                }
            }
        }

        Assert::assertTrue($foundCurrent, 'Не найдена кнопка с номером текущей страницы');

        if ($currentPage < $totalPages) {
            Assert::assertTrue($foundNext, 'Должна быть кнопка "Следующая"');
        } else {
            Assert::assertFalse($foundNext, 'Не должно быть кнопки "Следующая" на последней странице');
        }

        if ($currentPage > 1) {
            Assert::assertTrue($foundPrev, 'Должна быть кнопка "Предыдущая"');
        } else {
            Assert::assertFalse($foundPrev, 'Не должно быть кнопки "Предыдущая" на первой странице');
        }
    }
}
