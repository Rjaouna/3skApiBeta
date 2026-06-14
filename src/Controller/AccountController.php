<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\User;
use App\Repository\CustomerOrderRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class AccountController extends AbstractController
{
    public function __construct(
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly NotificationService $notificationService
    ) {
    }

    #[Route('/mon-profil', name: 'account_profile', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function profile(CustomerOrderRepository $orderRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('account/profile.html.twig', [
            'crudToken' => $this->csrfTokenManager->getToken('account_profile_update')->getValue(),
            'orderCounts' => $orderRepository->countByStatusForCustomer($user),
        ]);
    }

    #[Route('/mon-profil/update', name: 'account_profile_update', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function update(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            $payload = $request->request->all();
        }

        $token = (string) ($payload['_token'] ?? '');
        if (!$this->isCsrfTokenValid('account_profile_update', $token)) {
            return $this->json([
                'ok' => false,
                'message' => 'Token CSRF invalide.',
            ], 403);
        }

        $companyName = trim((string) ($payload['companyName'] ?? ''));
        $customerCode = trim((string) ($payload['customerCode'] ?? ''));
        $address1 = trim((string) ($payload['address1'] ?? ''));
        $address2 = trim((string) ($payload['address2'] ?? ''));
        $city = trim((string) ($payload['city'] ?? ''));
        $password = (string) ($payload['password'] ?? '');

        if (mb_strlen($companyName) > 255) {
            return $this->json([
                'ok' => false,
                'message' => 'Le nom de société est trop long.',
            ], 422);
        }

        if (mb_strlen($customerCode) > 255) {
            return $this->json([
                'ok' => false,
                'message' => 'Le code client est trop long.',
            ], 422);
        }

        if (mb_strlen($city) > 50) {
            return $this->json([
                'ok' => false,
                'message' => 'La ville est trop longue.',
            ], 422);
        }

        if ($password !== '' && mb_strlen($password) < 6) {
            return $this->json([
                'ok' => false,
                'message' => 'Le mot de passe doit contenir au moins 6 caractères.',
            ], 422);
        }

        $oldCompanyName = $user->getCompanyName();
        $oldCustomerCode = $user->getCustomerCode();
        $oldAddress1 = $user->getAddress1();
        $oldAddress2 = $user->getAddress2();
        $oldCity = $user->getCity();

        $newCompanyName = $companyName !== '' ? $companyName : null;
        $newCustomerCode = $customerCode !== '' ? $customerCode : null;
        $newAddress1 = $address1 !== '' ? $address1 : null;
        $newAddress2 = $address2 !== '' ? $address2 : null;
        $newCity = $city !== '' ? $city : null;

        $changedFields = [];

        if ($oldCompanyName !== $newCompanyName) {
            $changedFields[] = 'companyName';
        }
        if ($oldCustomerCode !== $newCustomerCode) {
            $changedFields[] = 'customerCode';
        }
        if ($oldAddress1 !== $newAddress1) {
            $changedFields[] = 'address1';
        }
        if ($oldAddress2 !== $newAddress2) {
            $changedFields[] = 'address2';
        }
        if ($oldCity !== $newCity) {
            $changedFields[] = 'city';
        }
        if ($password !== '') {
            $changedFields[] = 'password';
        }

        $user->setCompanyName($newCompanyName);
        $user->setCustomerCode($newCustomerCode);
        $user->setAddress1($newAddress1);
        $user->setAddress2($newAddress2);
        $user->setCity($newCity);

        if ($password !== '') {
            $user->setPassword($passwordHasher->hashPassword($user, $password));
        }

        $em->flush();

        if (!empty($changedFields)) {
            $this->notificationService->notifyProfileUpdated($user, $user, $changedFields);
        }

        return $this->json([
            'ok' => true,
            'message' => 'Profil mis à jour avec succès.',
        ]);
    }
}