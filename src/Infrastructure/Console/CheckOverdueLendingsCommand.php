<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Infrastructure\Console;

use Dinargab\LibraryBot\Application\Lending\UseCase\GetOverdueLendingsUseCase;
use Dinargab\LibraryBot\Application\Shared\DTO\LendingDTO;
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
        private readonly GetOverdueLendingsUseCase $getOverdueLendingsUseCase,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        /** @var array<LendingDTO> $overdueLendings */
        $overdueLendings = ($this->getOverdueLendingsUseCase)();

        $output->writeln(sprintf('Нашел %d просроченных выдач', count($overdueLendings)));

        foreach ($overdueLendings as $lending) {

            $output->writeln(sprintf(
                '  - Книга #%d: %s - %s (просрочена на %d дней/дня)',
                $lending->id,
                $lending->bookAuthor,
                $lending->bookTitle,
                $lending->daysUntilDue
            ));
        }

        return Command::SUCCESS;
    }
}
