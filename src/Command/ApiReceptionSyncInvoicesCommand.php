<?php

namespace App\Command;

use App\Exception\ApiReceptionException;
use App\Service\EbpInvoiceImportService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:api-reception:sync-invoices', description: 'Rapatrie les factures EBP terminées depuis ApiRecept.')]
final class ApiReceptionSyncInvoicesCommand extends Command
{
    public function __construct(private readonly EbpInvoiceImportService $imports) { parent::__construct(); }

    protected function configure(): void
    {
        $this->addOption('request-all', null, InputOption::VALUE_NONE, 'Demande d’abord le chargement des factures pour tous les clients EBP actifs.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        try {
            if ($input->getOption('request-all')) {
                $queued = $this->imports->requestAll();
                $io->comment(sprintf('%d demande(s) ajoutée(s), %d échec(s) de demande.', $queued['requested'], $queued['failed']));
            }
            $result = $this->imports->refresh();
        } catch (ApiReceptionException $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }
        $io->success(sprintf('%d facture(s) enregistrée(s) ; %d demande(s) terminée(s), %d en attente, %d en erreur.', $result['invoices'], $result['completed'], $result['pending'], $result['failed']));

        return Command::SUCCESS;
    }
}
