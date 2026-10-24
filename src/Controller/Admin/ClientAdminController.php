<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\CustomerOrderRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\NotificationService;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/admin/clients', name: 'admin_clients_')]
#[IsGranted('ROLE_ADMIN')]
class ClientAdminController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/clients/index.html.twig', [
            'crudToken' => $this->container->get('security.csrf.token_manager')->getToken('admin_clients_crud')->getValue(),
        ]);
    }

    #[Route('/list', name: 'list', methods: ['GET'])]
    public function list(Request $request, UserRepository $userRepository, CustomerOrderRepository $orderRepository): JsonResponse
    {
        $q = mb_strtolower(trim((string)$request->query->get('q', '')));
        $active = trim((string)$request->query->get('active', ''));
        $city = mb_strtolower(trim((string)$request->query->get('city', '')));
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 20;

        $monthStart = new \DateTimeImmutable('first day of this month 00:00:00');
        $orderStats = $orderRepository->getClientOrderStats($monthStart, $monthStart->modify('first day of next month'));

        $users = $userRepository->findBy([], ['id' => 'DESC']);

        $items = [];
        foreach ($users as $user) {
            if (!$user instanceof User) {
                continue;
            }

            // On masque les admins dans cette page "clients"
            if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
                continue;
            }

            $row = $this->normalizeUser($user);
            $row['lastOrderAt'] = $orderStats[$user->getId()]['lastOrderAt'] ?? null;
            $row['monthlyOrders'] = $orderStats[$user->getId()]['monthlyOrders'] ?? 0;

            if ($q !== '') {
                $haystack = mb_strtolower(implode(' ', [
                    $row['email'] ?? '',
                    $row['companyName'] ?? '',
                    $row['customerCode'] ?? '',
                    $row['address1'] ?? '',
                    $row['address2'] ?? '',
                    $row['city'] ?? '',
                ]));

                if (!str_contains($haystack, $q)) {
                    continue;
                }
            }

            if ($active === '1' && !$row['isActive']) {
                continue;
            }

            if ($active === '0' && $row['isActive']) {
                continue;
            }

            if ($city !== '' && !str_contains(mb_strtolower((string) ($row['city'] ?? '')), $city)) {
                continue;
            }

            $items[] = $row;
        }

        usort($items, static fn (array $a, array $b): int =>
            strcmp($b['lastOrderAt'] ?? '', $a['lastOrderAt'] ?? '') ?: ($b['id'] <=> $a['id'])
        );

        $total = count($items);
        $pages = max(1, (int) ceil($total / $limit));
        $page = min($page, $pages);
        $items = array_slice($items, ($page - 1) * $limit, $limit);

        return $this->json([
            'ok' => true,
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'limit' => $limit,
        ]);
    }

    #[Route('/create', name: 'create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        NotificationService $notificationService
    ): JsonResponse {
        $data = json_decode((string)$request->getContent(), true) ?: [];

        if (!isset($data['_token']) || !$this->isCsrfTokenValid('admin_clients_crud', $data['_token'])) {
            return $this->json(['ok' => false, 'message' => 'CSRF invalide'], 403);
        }

        $email = mb_strtolower(trim((string)($data['email'] ?? '')));
        $password = (string)($data['password'] ?? '');

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['ok' => false, 'message' => 'Email invalide'], 400);
        }

        if (mb_strlen($password) < 6) {
            return $this->json(['ok' => false, 'message' => 'Le mot de passe doit contenir au moins 6 caractères'], 400);
        }

        $user = new User();
        $user->setEmail($email);
        $user->setPassword($passwordHasher->hashPassword($user, $password));
        $user->setRoles(['ROLE_USER']);
        $user->setCompanyName($this->nullableTrim($data['companyName'] ?? null));
        $user->setCustomerCode($this->nullableTrim($data['customerCode'] ?? null));
        $user->setIsActive((bool)($data['isActive'] ?? true));
        $user->setAddress1($this->nullableTrim($data['address1'] ?? null));
        $user->setAddress2($this->nullableTrim($data['address2'] ?? null));
        $user->setCity($this->nullableTrim($data['city'] ?? null));

        try {
            $em->persist($user);
            $em->flush();
            $notificationService->notifyNewAccountCreated($user, $user);
        } catch (UniqueConstraintViolationException) {
            return $this->json(['ok' => false, 'message' => 'Cet email existe déjà'], 409);
        }

        return $this->json([
            'ok' => true,
            'message' => 'Client créé avec succès',
            'item' => $this->normalizeUser($user),
        ]);
    }

    #[Route('/update/{id}', name: 'update', methods: ['POST'])]
    public function update(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $data = json_decode((string)$request->getContent(), true) ?: [];

        if (!isset($data['_token']) || !$this->isCsrfTokenValid('admin_clients_crud', $data['_token'])) {
            return $this->json(['ok' => false, 'message' => 'CSRF invalide'], 403);
        }

        $user = $userRepository->find($id);
        if (!$user instanceof User) {
            return $this->json(['ok' => false, 'message' => 'Client introuvable'], 404);
        }

        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return $this->json(['ok' => false, 'message' => 'Modification refusée pour ce compte'], 403);
        }

        $email = mb_strtolower(trim((string)($data['email'] ?? '')));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['ok' => false, 'message' => 'Email invalide'], 400);
        }

        $user->setEmail($email);
        $user->setCompanyName($this->nullableTrim($data['companyName'] ?? null));
        $user->setCustomerCode($this->nullableTrim($data['customerCode'] ?? null));
        $user->setIsActive((bool)($data['isActive'] ?? false));
        $user->setAddress1($this->nullableTrim($data['address1'] ?? null));
        $user->setAddress2($this->nullableTrim($data['address2'] ?? null));
        $user->setCity($this->nullableTrim($data['city'] ?? null));

        $password = trim((string)($data['password'] ?? ''));
        if ($password !== '') {
            if (mb_strlen($password) < 6) {
                return $this->json(['ok' => false, 'message' => 'Le mot de passe doit contenir au moins 6 caractères'], 400);
            }

            $user->setPassword($passwordHasher->hashPassword($user, $password));
        }

        try {
            $em->flush();
        } catch (UniqueConstraintViolationException) {
            return $this->json(['ok' => false, 'message' => 'Cet email existe déjà'], 409);
        }

        return $this->json([
            'ok' => true,
            'message' => 'Client modifié avec succès',
            'item' => $this->normalizeUser($user),
        ]);
    }

    #[Route('/delete/{id}', name: 'delete', methods: ['POST'])]
    public function delete(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        UserRepository $userRepository
    ): JsonResponse {
        $data = json_decode((string)$request->getContent(), true) ?: [];

        if (!isset($data['_token']) || !$this->isCsrfTokenValid('admin_clients_crud', $data['_token'])) {
            return $this->json(['ok' => false, 'message' => 'CSRF invalide'], 403);
        }

        $user = $userRepository->find($id);
        if (!$user instanceof User) {
            return $this->json(['ok' => false, 'message' => 'Client introuvable'], 404);
        }

        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return $this->json(['ok' => false, 'message' => 'Suppression refusée pour ce compte'], 403);
        }

        $em->remove($user);
        $em->flush();

        return $this->json([
            'ok' => true,
            'message' => 'Client supprimé avec succès',
        ]);
    }

    private function normalizeUser(User $user): array
    {
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'companyName' => $user->getCompanyName(),
            'customerCode' => $user->getCustomerCode(),
            'source' => $user->getEbpUniqueId() !== null ? 'EBP' : 'Site',
            'createdAt' => $user->getCreatedAt()?->format(DATE_ATOM),
            'updatedAt' => $user->getUpdatedAt()?->format(DATE_ATOM),
            'isActive' => (bool)$user->isActive(),
            'address1' => $user->getAddress1(),
            'address2' => $user->getAddress2(),
            'city' => $user->getCity(),
        ];
    }

    private function nullableTrim(mixed $value): ?string
    {
        $v = trim((string)($value ?? ''));
        return $v === '' ? null : $v;
    }
}
