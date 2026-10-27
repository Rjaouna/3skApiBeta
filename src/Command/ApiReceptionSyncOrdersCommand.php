<?php

namespace App\Command;

use App\Exception\ApiReceptionException;
use App\Service\EbpOrderExportService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:api-reception:sync-orders', description: 'Transmet les commandes du site vers EBP et actualise leur état.')]
final class ApiReceptionSyncOrdersCommand extends Command
{
    public function __construct(private readonly EbpOrderExportService $exports)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        try {
            $result = $this->exports->synchronize();
        } catch (ApiReceptionException $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $io->success(sprintf(
            '%d commande(s) envoyée(s), %d disponible(s) dans EBP, %d en attente de reprise.',
            $result['dispatched'],
            $result['available'],
            $result['deliveryFailed'] + $result['remoteFailed'],
        ));

        return Command::SUCCESS;
    }
}
