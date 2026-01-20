<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Infrastructure\Console;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;

#[AsCommand(
    name: 'library:notify-deadline',
    description: 'Рассылка уведомлений за 2-0 дней до наступления срок окончания выдачи'
)]
class NotifyDeadLine extends Command
{

    public function __construct(

    )
    {

    }
    public function __invoke()
    {

    }
}
