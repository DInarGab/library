<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Infrastructure\Bot;

use Dinargab\LibraryBot\Infrastructure\Queue\Notification\TelegramUpdateMessage;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Common\Update;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'telegram:polling',
    description: 'Запуск бота в режиме polling с отправкой в очередь'
)]
class TelegramPollingCommand extends Command
{
    private bool $shouldStop = false;

    public function __construct(
        private readonly Nutgram $bot,
        private readonly MessageBusInterface $messageBus,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('timeout', 't', InputOption::VALUE_OPTIONAL, 'Polling timeout', 30)
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Limit updates per request', 100)
            ->addOption('direct', 'd', InputOption::VALUE_NONE, 'Process directly without queue');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $timeout = (int) $input->getOption('timeout');
        $limit = (int) $input->getOption('limit');
        $direct = $input->getOption('direct');

        // Обработка сигналов для graceful shutdown
        if (extension_loaded('pcntl')) {
            pcntl_async_signals(true);
            pcntl_signal(SIGTERM, fn() => $this->shouldStop = true);
            pcntl_signal(SIGINT, fn() => $this->shouldStop = true);
        }

        $io->success('Telegram polling started' . ($direct ? ' (direct mode)' : ' (queue mode)'));

        $offset = 0;

        while (!$this->shouldStop) {
            try {
                $updates = $this->bot->getUpdates(
                    offset: $offset,
                    limit: $limit,
                    timeout: $timeout
                );

                foreach ($updates as $update) {
                    $offset = $update->update_id + 1;

                    if ($direct) {
                        // Прямая обработка (для отладки)
                        $this->processDirectly($update, $io);
                    } else {
                        // Отправка в очередь
                        $this->dispatchToQueue($update, $io);
                    }
                }

            } catch (\Throwable $e) {
                $io->error('Error: ' . $e->getMessage());

                // Пауза перед повторной попыткой
                sleep(5);
            }
        }

        $io->warning('Polling stopped gracefully');
        return Command::SUCCESS;
    }

    private function dispatchToQueue(Update $update, SymfonyStyle $io): void
    {
        $message = new TelegramUpdateMessage(
            update: $update,
            receivedAt: time()
        );

        $this->messageBus->dispatch($message);

        $io->writeln(sprintf(
            '[%s] Update #%d queued',
            date('H:i:s'),
            $update->update_id
        ));

    }

    private function processDirectly(Update $update, SymfonyStyle $io): void
    {
        $this->bot->processUpdate($update);

        $io->writeln(sprintf(
            '[%s] Update #%d processed directly',
            date('H:i:s'),
            $update->update_id
        ));
    }
}
