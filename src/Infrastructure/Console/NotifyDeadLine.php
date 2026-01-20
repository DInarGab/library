<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Infrastructure\Console;

use Dinargab\LibraryBot\Application\Lending\UseCase\NotifyBeforeDeadlineUseCase;
use Dinargab\LibraryBot\Application\Shared\DTO\LendingDTO;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'library:notify-deadline',
    description: 'Рассылка уведомлений за 2-0 дней до наступления срок окончания выдачи'
)]
class NotifyDeadLine extends Command
{

    public function __construct(
        public NotifyBeforeDeadlineUseCase $notifyBeforeDeadlineUseCase,
    )
    {
        parent::__construct();
    }
    public function __invoke(InputInterface $input, OutputInterface $output): int
    {
        $lendingsResponse = ($this->notifyBeforeDeadlineUseCase)();

        $output->writeln(sprintf('Нашел %d выдач, которые подходят к концу', count($lendingsResponse->lendingsDto)));

        foreach ($lendingsResponse->lendingsDto as $lending) {
            /** @var LendingDTO $lending*/
            $output->writeln(sprintf(
                '  - Книга #%d: %s - %s (подходит к сроку возврата, должны вернуть через %d)',
                $lending->id,
                $lending->bookAuthor,
                $lending->bookTitle,
                $lending->daysUntilDue
            ));
        }
        return Command::SUCCESS;
    }
}
