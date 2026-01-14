<?php
/** @var SergiX44\Nutgram\Nutgram $bot */

use Dinargab\LibraryBot\Infrastructure\Bot\Commands\Admin\AddBookCommand;
use Dinargab\LibraryBot\Infrastructure\Bot\Commands\Admin\DeleteBookCommand;
use Dinargab\LibraryBot\Infrastructure\Bot\Commands\BookDetailPageCommand;
use Dinargab\LibraryBot\Infrastructure\Bot\Commands\ListBooksCommand;
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
$bot->onCommand('list_books', ListBooksCommand::class);

$bot->onCallbackQueryData('book_detail:{bookId}', BookDetailPageCommand::class);
$bot->onCallbackQueryData(ListBooksCommand::PAGINATION_PREFIX . ':{page}', ListBooksCommand::class);

$bot->group(function ($bot) {
    $bot->onCommand('add_book', AddBookCommand::class);
    $bot->onCallbackQueryData('delete_book:{bookId}', DeleteBookCommand::class);

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
