<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Infrastructure\Console;

use Dinargab\LibraryBot\Domain\Event\EventDispatcherInterface;
use Dinargab\LibraryBot\Domain\Event\Events\LendingOverdueEvent;
use Dinargab\LibraryBot\Domain\Lending\Repository\LendingRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'library:check-overdue',
    description: 'Проверка на просроченные выдачи'
)]
class CheckOverdueLendingsCommand extends Command
{
    public function __construct(
        private readonly LendingRepositoryInterface $lendingRepository,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $overdueLendings = $this->lendingRepository->findOverdue();

        $output->writeln(sprintf('Found %d overdue lendings', count($overdueLendings)));

        foreach ($overdueLendings as $lending) {
            $lending->markAsOverdue();
            $this->lendingRepository->save($lending);

            $this->eventDispatcher->dispatch(new LendingOverdueEvent(
                lendingId: $lending->getId(),
                bookId: $lending->getBookCopy()->getBook()->getId(),
                bookTitle: $lending->getBookCopy()->getBook()->getTitle(),
                userId: $lending->getUser()->getId(),
                userTelegramId: (string) $lending->getUser()->getTelegramId(),
                daysOverdue: abs($lending->getDaysUntilDue())
            ));

            $output->writeln(sprintf(
                '  - Книга #%d: %s (просрочена на %d дней/дня)',
                $lending->getId(),
                $lending->getBookCopy()->getBook()->getTitle(),
                abs($lending->getDaysUntilDue())
            ));
        }

        return Command::SUCCESS;
    }
}
