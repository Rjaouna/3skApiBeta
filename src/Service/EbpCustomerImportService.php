<?php

namespace App\Service;

use App\Entity\EbpCustomerImport;
use App\Entity\User;
use App\Exception\ApiReceptionException;
use App\Repository\EbpCustomerImportRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class EbpCustomerImportService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EbpCustomerImportRepository $imports,
        private readonly UserRepository $users,
        private readonly ApiReceptionConnectionProvider $connections,
        private readonly ApiReceptionClient $api,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly NotificationService $notifications,
    ) {
    }

    /** @return array<string, mixed> */
    public function request(string $uniqueId): array
    {
        $uniqueId = $this->uniqueId($uniqueId);
        $remote = $this->api->requestCustomerImport($this->connections->get(), $uniqueId);
        $item = $remote['item'] ?? null;
        if (($remote['ok'] ?? false) !== true || !is_array($item) || !is_int($item['id'] ?? null) || strtoupper((string) ($item['uniqueId'] ?? '')) !== $uniqueId) {
            throw new ApiReceptionException('ApiRecept n’a pas confirmé la demande d’import client.');
        }

        $import = $this->imports->findOneBy(['uniqueId' => $uniqueId]);
        if (!$import instanceof EbpCustomerImport) {
            $import = new EbpCustomerImport($uniqueId);
            $this->entityManager->persist($import);
        }
        $import->queue($item['id']);
        $this->entityManager->flush();

        return $import->toArray();
    }

    /** @return array{checked: int, created: int, updated: int, failed: int, pending: int} */
    public function refresh(): array
    {
        $result = ['checked' => 0, 'created' => 0, 'updated' => 0, 'failed' => 0, 'pending' => 0];
        $connection = $this->connections->get();

        foreach ($this->imports->waitingForRemote() as $import) {
            ++$result['checked'];
            $remoteId = $import->getRemoteRequestId();
            if ($remoteId === null) {
                $import->fail('La référence de la demande ApiRecept est absente.');
                ++$result['failed'];
                $this->entityManager->flush();
                continue;
            }

            $remote = $this->api->customerImport($connection, $remoteId);
            $item = $remote['item'] ?? null;
            if (($remote['ok'] ?? false) !== true || !is_array($item) || strtoupper((string) ($item['uniqueId'] ?? '')) !== $import->getUniqueId()) {
                $import->fail('ApiRecept a retourné une réponse client invalide.');
                ++$result['failed'];
                $this->entityManager->flush();
                continue;
            }

            $status = (string) ($item['status'] ?? '');
            if ($status === EbpCustomerImport::STATUS_PENDING) {
                $import->waiting();
                ++$result['pending'];
                $this->entityManager->flush();
                continue;
            }
            if ($status === EbpCustomerImport::STATUS_FAILED) {
                $import->fail($this->text($item['message'] ?? '', 1000) ?: 'ApiLocal n’a pas pu exporter ce client EBP.');
                ++$result['failed'];
                $this->entityManager->flush();
                continue;
            }
            if ($status !== 'completed' || !is_array($item['customer'] ?? null)) {
                $import->fail('ApiRecept a retourné un statut client invalide.');
                ++$result['failed'];
                $this->entityManager->flush();
                continue;
            }

            try {
                $customer = $this->customer($item['customer'], $import->getUniqueId());
                $this->createUser($import, $customer, $result);
            } catch (\DomainException $exception) {
                $import->fail($exception->getMessage());
                ++$result['failed'];
            }
            $this->entityManager->flush();
        }

        return $result;
    }

    public function delete(EbpCustomerImport $import): void
    {
        $remoteId = $import->getRemoteRequestId();
        if ($remoteId !== null) {
            $result = $this->api->deleteCustomerImport($this->connections->get(), $remoteId);
            if (($result['ok'] ?? false) !== true) {
                throw new ApiReceptionException('ApiRecept n’a pas confirmé la suppression de la demande client.');
            }
        }
        $this->entityManager->remove($import);
        $this->entityManager->flush();
    }

    public function clear(): int
    {
        $remote = $this->api->clearCustomerImports($this->connections->get());
        if (($remote['ok'] ?? false) !== true) {
            throw new ApiReceptionException('ApiRecept n’a pas confirmé le nettoyage des demandes clients.');
        }
        $deleted = $this->entityManager->createQuery('DELETE FROM App\\Entity\\EbpCustomerImport i')->execute();
        $this->entityManager->clear(EbpCustomerImport::class);

        return $deleted;
    }

    /**
     * @param array<string, mixed> $source
     *
     * @return array<string, scalar|null>
     */
    private function customer(array $source, string $uniqueId): array
    {
        if (strtoupper((string) ($source['uniqueId'] ?? '')) !== $uniqueId) {
            throw new \DomainException('La fiche reçue ne correspond pas à la demande client.');
        }

        $customer = ['uniqueId' => $uniqueId];
        foreach (['ebpId', 'customerCode', 'companyName', 'email', 'phone', 'address1', 'address2', 'city', 'country'] as $field) {
            $value = $source[$field] ?? null;
            if ($value !== null && !is_scalar($value)) {
                throw new \DomainException('La fiche client reçue contient une valeur invalide.');
            }
            $customer[$field] = $value === null ? null : $this->text($value, $field === 'email' ? 180 : 1000);
        }

        return $customer;
    }

    /** @param array<string, scalar|null> $customer
     *  @param array{checked: int, created: int, updated: int, failed: int, pending: int} $result
     */
    private function createUser(EbpCustomerImport $import, array $customer, array &$result): void
    {
        $customerCode = $this->customerCode($customer, $import->getUniqueId());
        $existing = $this->users->findOneBy(['ebpUniqueId' => $import->getUniqueId()]);
        if ($existing instanceof User) {
            $savedCode = $existing->getCustomerCode();
            if ($customerCode !== null && ($savedCode === null || trim($savedCode) === '' || strcasecmp(trim($savedCode), $import->getUniqueId()) === 0)) {
                $existing->setCustomerCode($customerCode);
            }
            $import->created($existing, $customer);
            ++$result['updated'];

            return;
        }

        $email = mb_strtolower($this->text($customer['email'] ?? '', 180));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $import->fail('Le client EBP ne possède pas d’adresse e-mail exploitable pour créer son compte.');
            ++$result['failed'];

            return;
        }
        if ($this->users->findOneBy(['email' => $email]) instanceof User) {
            $import->fail('Cette adresse e-mail est déjà utilisée par un autre compte du site.');
            ++$result['failed'];

            return;
        }

        $user = (new User())
            ->setEmail($email)
            ->setRoles(['ROLE_USER'])
            ->setIsActive(true)
            ->setEbpUniqueId($import->getUniqueId())
            ->setCompanyName($this->nullableText($customer['companyName'] ?? null, 255))
            ->setCustomerCode($customerCode)
            ->setAddress1($this->nullableText($customer['address1'] ?? null, 5000))
            ->setAddress2($this->nullableText($customer['address2'] ?? null, 5000))
            ->setCity($this->nullableText($customer['city'] ?? null, 50));
        $temporaryPassword = rtrim(strtr(base64_encode(random_bytes(24)), '+/', '-_'), '=');
        $user->setPassword($this->passwordHasher->hashPassword($user, $temporaryPassword));
        $this->entityManager->persist($user);
        $import->created($user, $customer);
        $this->notifications->notifyNewAccountCreated($user, $user);
        ++$result['created'];
    }

    /** @param array<string, scalar|null> $customer */
    private function customerCode(array $customer, string $uniqueId): ?string
    {
        foreach (['customerCode', 'ebpId'] as $field) {
            $code = $this->nullableText($customer[$field] ?? null, 255);
            if ($code !== null && strcasecmp($code, $uniqueId) !== 0) {
                return $code;
            }
        }

        return null;
    }

    private function uniqueId(string $value): string
    {
        $uniqueId = strtoupper(trim($value));
        if (preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{12}$/D', $uniqueId) !== 1) {
            throw new \DomainException('Saisissez un UniqueId EBP valide.');
        }

        return $uniqueId;
    }

    private function text(mixed $value, int $limit): string
    {
        return mb_substr(trim((string) $value), 0, $limit);
    }

    private function nullableText(mixed $value, int $limit): ?string
    {
        $text = $this->text($value, $limit);

        return $text === '' ? null : $text;
    }
}
