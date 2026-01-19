<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Infrastructure\Bot\Service;

use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

class KeyboardService
{

    /**
     * Создает клавиатуру с элементами списка и пагинацией
     *
     * @param array $items Массив элементов для отображения
     * @param int $currentPage Текущая страница
     * @param int $totalPages Всего страниц
     * @param callable $itemCallback Функция для создания кнопки элемента
     * @param string $paginationCallbackPrefix Префикс для пагинации
     * @param array $extraButtons Дополнительные кнопки
     * @return InlineKeyboardMarkup
     */
    public function createListWithPagination(
        array $items,
        int $currentPage,
        int $totalPages,
        callable $itemCallback,
        string $paginationCallbackPrefix = 'page',
        array $extraButtons = [],
        string $closeCallback = 'close'
    ): InlineKeyboardMarkup {
        $keyboard = InlineKeyboardMarkup::make();

        foreach ($items as $key => $item) {
            $button = $itemCallback($item, $key);
            $keyboard->addRow($button);
        }

        if (!empty($extraButtons)) {
            $chunks = array_chunk($extraButtons, 2);
            foreach ($chunks as $chunk) {
                $keyboard->addRow(...$chunk);
            }
        }

        $paginationRow = $this->createPaginationRow($currentPage, $totalPages, $paginationCallbackPrefix);
        $keyboard->addRow(...$paginationRow);

        if ($closeCallback !== null) {
            $keyboard->addRow(InlineKeyboardButton::make('❌ Закрыть', callback_data: $closeCallback));
        }

        return $keyboard;
    }

    /**
     * Создает строку пагинации
     *
     * @param int $currentPage
     * @param int $totalPages
     * @param string $callbackPrefix
     * @return array
     */
    public function createPaginationRow(
        int $currentPage,
        int $totalPages,
        string $callbackPrefix = 'page'
    ): array {
        $buttons = [];

        if ($currentPage > 1) {
            $buttons[] = InlineKeyboardButton::make(
                '◀️ Предыдущая',
                callback_data: "{$callbackPrefix}:" . ($currentPage - 1)
            );
        }

        $buttons[] = InlineKeyboardButton::make(
            "Страница {$currentPage}/{$totalPages}",
            callback_data: 'current'
        );

        if ($currentPage < $totalPages) {
            $buttons[] = InlineKeyboardButton::make(
                'Следующая ▶️',
                callback_data: "{$callbackPrefix}:" . ($currentPage + 1)
            );
        }

        return $buttons;
    }

    /**
     * Создает навигационную клавиатуру (назад/закрыть и т.д.)
     *
     * @param string|null $backCallback Callback для кнопки "Назад"
     * @param string|null $closeCallback Callback для кнопки "Закрыть"
     * @param array $additionalButtons Дополнительные кнопки
     * @return InlineKeyboardMarkup
     */
    public function createNavigationKeyboard(
        ?string $backCallback = null,
        ?string $closeCallback = 'close',
        array $additionalButtons = []
    ): InlineKeyboardMarkup {
        $keyboard = InlineKeyboardMarkup::make();

        $buttons = [];

        if (!empty($additionalButtons)) {
            foreach ($additionalButtons as $additionalButton) {
                $buttons[] = $additionalButton;
            }
        }

        if ($backCallback !== null) {
            $buttons[] = InlineKeyboardButton::make('⬅️ Назад', callback_data: $backCallback);
        }

        if ($closeCallback !== null) {
            $buttons[] = InlineKeyboardButton::make('❌ Закрыть', callback_data: $closeCallback);
        }


        if (!empty($buttons)) {
            $buttonsChunked = array_chunk($buttons, 2);
            foreach ($buttonsChunked as $chunk) {
                $keyboard->addRow(...$chunk);
            }
        }

        return $keyboard;
    }

    /**
     * Построение клавиатуры подтверждения
     */
    public function buildConfirmationKeyboard(string $prefix): InlineKeyboardMarkup
    {
        return InlineKeyboardMarkup::make()
            ->addRow(
                InlineKeyboardButton::make(
                    "Да, подтвердить",
                    callback_data: "{$prefix}_confirm:yes"
                ),
                InlineKeyboardButton::make(
                    "Отмена",
                    callback_data: "close"
                )
            );
    }


}
