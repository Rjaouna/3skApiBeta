<?php

namespace App\Command;

use App\Exception\ApiReceptionException;
use App\Service\ApiCatalogSynchronizer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:api-reception:sync-catalog',
    description: 'Synchronise le catalogue local depuis ApiRecept.',
)]
final class ApiReceptionSyncCatalogCommand extends Command
{
    public function __construct(private readonly ApiCatalogSynchronizer $synchronizer)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('if-due', null, InputOption::VALUE_NONE, 'Quitte sans synchroniser si la fréquence configurée n’est pas atteinte.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        if ($input->getOption('if-due') && !$this->synchronizer->isDue()) {
            $io->comment('Synchronisation non nécessaire pour le moment.');

            return Command::SUCCESS;
        }

        try {
            $run = $this->synchronizer->synchronize()->toSummary();
        } catch (ApiReceptionException $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $io->success((string) $run['message']);

        return Command::SUCCESS;
    }
}
