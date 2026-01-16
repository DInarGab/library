<?php
/** @var SergiX44\Nutgram\Nutgram $bot */

use Dinargab\LibraryBot\Infrastructure\Bot\Commands\Admin\AddBookConversation;
use Dinargab\LibraryBot\Infrastructure\Bot\Commands\Admin\DeleteBookCommand;
use Dinargab\LibraryBot\Infrastructure\Bot\Commands\Admin\LendBookConversation;
use Dinargab\LibraryBot\Infrastructure\Bot\Commands\Admin\ReturnBookCommand;
use Dinargab\LibraryBot\Infrastructure\Bot\Commands\BookDetailPageCommand;
use Dinargab\LibraryBot\Infrastructure\Bot\Commands\LendingsDetailPageCommand;
use Dinargab\LibraryBot\Infrastructure\Bot\Commands\ListBooksCommand;
use Dinargab\LibraryBot\Infrastructure\Bot\Commands\ListLendingsCommand;
use Dinargab\LibraryBot\Infrastructure\Bot\Commands\StartCommand;
use Dinargab\LibraryBot\Infrastructure\Bot\Middlewares\AdminMiddleware;
use Dinargab\LibraryBot\Infrastructure\Bot\Middlewares\UserAuthMiddleware;
use SergiX44\Nutgram\Conversations\Conversation;
use SergiX44\Nutgram\Nutgram;


Conversation::refreshOnDeserialize();

//$bot->onCommand('start', function (Nutgram $bot) {
//    return $bot->sendMessage('Hello, world!');
//})->description('The start command!');
$bot->middleware(UserAuthMiddleware::class);
$bot->onCommand('start', StartCommand::class);

//Список книг и описание книги
$bot->onCommand('list_books', ListBooksCommand::class);
$bot->onCallbackQueryData('book_detail:{bookId}', BookDetailPageCommand::class);
$bot->onCallbackQueryData(ListBooksCommand::PAGINATION_PREFIX . ':{page}', ListBooksCommand::class);
$bot->onCallbackQueryData('lending_detail:{lendingId}', LendingsDetailPageCommand::class);
//Список выдач книг и описание выдач
$bot->onCommand(ListLendingsCommand::PAGINATION_PREFIX, ListLendingsCommand::class);
$bot->onCallbackQueryData(ListLendingsCommand::PAGINATION_PREFIX . ':{page}', ListLendingsCommand::class);

$bot->group(function ($bot) {
    $bot->onCommand('add_book', AddBookConversation::class);

    $bot->onCallbackQueryData('delete_book:{bookId}', DeleteBookCommand::class);
    $bot->onCallbackQueryData(LendBookConversation::CALLBACK_PREFIX . ':{bookId}', LendBookConversation::class);
    $bot->onCallbackQueryData(ReturnBookCommand::RETURN_BOOK_PREFIX . ":{lendingId}", ReturnBookCommand::class);
})->middleware(AdminMiddleware::class);

$bot->onCallbackQueryData('close', function (Nutgram $bot) {
    $message = $bot->callbackQuery()?->message;

    if ($message) {
        try {
            $bot->deleteMessage(
                chat_id: $message->chat->id,
                message_id: $message->message_id
            );
        } catch (\Exception $e) {
            //Ничего не делаем, сообщение недоступно
        }
    }

    $bot->answerCallbackQuery();
});

$bot->onCallbackQueryData('current_page', function (Nutgram $bot) {
    $bot->answerCallbackQuery();
});

$bot->onCallbackQueryData('current', function (Nutgram $bot) {
    $bot->answerCallbackQuery();
});
