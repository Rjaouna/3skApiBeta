<?php

namespace App\Service;

use App\Entity\CustomerInvoice;
use App\Entity\EbpInvoiceImport;
use App\Entity\User;
use App\Exception\ApiReceptionException;
use App\Repository\CustomerInvoiceRepository;
use App\Repository\EbpInvoiceImportRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Lock\LockFactory;

final class EbpInvoiceImportService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EbpInvoiceImportRepository $imports,
        private readonly CustomerInvoiceRepository $invoices,
        private readonly UserRepository $users,
        private readonly ApiReceptionConnectionProvider $connections,
        private readonly ApiReceptionClient $api,
        private readonly LockFactory $locks,
    ) {}

    /** @return array<string, mixed> */
    public function request(User $user): array
    {
        $uniqueId = $this->uniqueId($user);
        $remote = $this->api->requestInvoiceImport($this->connections->get(), $uniqueId);
        $item = $remote['item'] ?? null;
        if (($remote['ok'] ?? false) !== true || !is_array($item) || !is_int($item['id'] ?? null) || strtoupper((string) ($item['uniqueId'] ?? '')) !== $uniqueId) {
            throw new ApiReceptionException('ApiRecept n’a pas confirmé la demande de documents.');
        }

        $import = $this->imports->forUser($user);
        if (!$import instanceof EbpInvoiceImport) {
            $import = new EbpInvoiceImport($user);
            $this->entityManager->persist($import);
        }
        $import->request($item);
        $this->entityManager->flush();

        return $import->toArray();
    }

    /** @return array{requested: int, failed: int} */
    public function requestAll(): array
    {
        $lock = $this->locks->createLock('ebp-invoice-import-request-all', 300.0);
        if (!$lock->acquire()) {
            throw new ApiReceptionException('Un chargement global des documents EBP est déjà en cours.');
        }
        try {
            $result = ['requested' => 0, 'failed' => 0];
            foreach ($this->users->findEbpLinkedActiveClients() as $user) {
                try {
                    $this->request($user);
                    ++$result['requested'];
                } catch (\Throwable) {
                    ++$result['failed'];
                }
            }

            return $result;
        } finally {
            $lock->release();
        }
    }

    /** @return array{checked: int, completed: int, failed: int, pending: int, invoices: int} */
    public function refresh(): array
    {
        $lock = $this->locks->createLock('ebp-invoice-import-refresh', 300.0);
        if (!$lock->acquire()) {
            throw new ApiReceptionException('Le contrôle des documents EBP est déjà en cours.');
        }
        try {
            $result = ['checked' => 0, 'completed' => 0, 'failed' => 0, 'pending' => 0, 'invoices' => 0];
            $connection = $this->connections->get();
            foreach ($this->imports->pending() as $import) {
                ++$result['checked'];
                $remoteId = $import->getRemoteRequestId();
                if ($remoteId === null) {
                    $import->applyRemote(['status' => EbpInvoiceImport::STATUS_FAILED, 'message' => 'La référence de la demande ApiRecept est absente.']);
                    ++$result['failed'];
                    continue;
                }
                try {
                    $remote = $this->api->invoiceImport($connection, $remoteId);
                    $item = $remote['item'] ?? null;
                    if (($remote['ok'] ?? false) !== true || !is_array($item)) {
                        throw new \DomainException('ApiRecept a retourné une réponse de documents invalide.');
                    }
                    $status = (string) ($item['status'] ?? '');
                    if ($status === EbpInvoiceImport::STATUS_PENDING) {
                        $import->applyRemote($item);
                        ++$result['pending'];
                        continue;
                    }
                    if ($status === EbpInvoiceImport::STATUS_FAILED) {
                        $import->applyRemote($item);
                        ++$result['failed'];
                        continue;
                    }
                    if ($status !== EbpInvoiceImport::STATUS_COMPLETED || !is_array($item['invoices'] ?? null)) {
                        throw new \DomainException('ApiRecept a retourné un statut de documents invalide.');
                    }
                    $result['invoices'] += $this->storeInvoices($import->getUser(), $item['invoices']);
                    $import->applyRemote($item);
                    ++$result['completed'];
                } catch (ApiReceptionException $exception) {
                    throw $exception;
                } catch (\Throwable $exception) {
                    $import->applyRemote(['status' => EbpInvoiceImport::STATUS_FAILED, 'message' => $exception->getMessage()]);
                    ++$result['failed'];
                }
            }
            $this->entityManager->flush();

            return $result;
        } finally {
            $lock->release();
        }
    }

    /** @return array<string, mixed>|null */
    public function status(User $user): ?array
    {
        return $this->imports->forUser($user)?->toArray();
    }

    /** @param list<mixed> $payload */
    private function storeInvoices(User $user, array $payload): int
    {
        $uniqueId = $this->uniqueId($user);
        $sourceIds = [];
        $stored = 0;
        foreach ($payload as $record) {
            if (!is_array($record) || array_is_list($record) || strtoupper((string) ($record['customerUniqueId'] ?? '')) !== $uniqueId) {
                throw new \DomainException('Un document reçu ne correspond pas au compte client.');
            }
            $sourceId = trim((string) ($record['sourceId'] ?? ''));
            if ($sourceId === '' || mb_strlen($sourceId) > 191) {
                throw new \DomainException('Un document reçu ne possède pas d’identifiant EBP utilisable.');
            }
            $sourceIds[] = $sourceId;
            $invoice = $this->invoices->findOneBy(['customer' => $user, 'sourceId' => $sourceId]);
            if (!$invoice instanceof CustomerInvoice) {
                $invoice = new CustomerInvoice($user, $record);
                $this->entityManager->persist($invoice);
            } else {
                $invoice->apply($record);
            }
            ++$stored;
        }
        $this->entityManager->flush();
        $this->invoices->removeMissingForCustomer($user, $sourceIds);

        return $stored;
    }

    private function uniqueId(User $user): string
    {
        $uniqueId = strtoupper(trim((string) $user->getEbpUniqueId()));
        if (preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{12}$/D', $uniqueId) !== 1) {
            throw new \DomainException('Votre compte n’est pas encore lié à un client EBP. Contactez votre administrateur.');
        }

        return $uniqueId;
    }
}
