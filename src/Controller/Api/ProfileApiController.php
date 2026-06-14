<?php

namespace App\Controller\Api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/profile', name: 'api_profile_')]
class ProfileApiController extends AbstractController
{
    #[Route('', name: 'get', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getProfile(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json([
                'ok' => false,
                'message' => 'Utilisateur introuvable',
            ], 401);
        }

        return $this->json([
            'ok' => true,
            'user' => $this->normalizeUser($user),
        ]);
    }

    #[Route('/update', name: 'update', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function updateProfile(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json([
                'ok' => false,
                'message' => 'Utilisateur introuvable',
            ], 401);
        }

        $data = json_decode((string)$request->getContent(), true) ?: [];

        if (!isset($data['_token']) || !$this->isCsrfTokenValid('profile_update', $data['_token'])) {
            return $this->json([
                'ok' => false,
                'message' => 'Token CSRF invalide',
            ], 403);
        }

        $companyName  = trim((string)($data['companyName'] ?? ''));
        $customerCode = trim((string)($data['customerCode'] ?? ''));
        $city         = trim((string)($data['city'] ?? ''));
        $address1     = trim((string)($data['address1'] ?? ''));
        $address2     = trim((string)($data['address2'] ?? ''));
        $currentPassword = (string)($data['currentPassword'] ?? '');
        $newPassword     = (string)($data['newPassword'] ?? '');

        if (mb_strlen($companyName) > 255) {
            return $this->json(['ok' => false, 'message' => 'Le nom de société est trop long'], 400);
        }

        if (mb_strlen($customerCode) > 255) {
            return $this->json(['ok' => false, 'message' => 'Le code client est trop long'], 400);
        }

        if (mb_strlen($city) > 50) {
            return $this->json(['ok' => false, 'message' => 'La ville est trop longue'], 400);
        }

        if ($newPassword !== '') {
            if (mb_strlen($newPassword) < 6) {
                return $this->json([
                    'ok' => false,
                    'message' => 'Le nouveau mot de passe doit contenir au moins 6 caractères',
                ], 400);
            }

            if ($currentPassword === '') {
                return $this->json([
                    'ok' => false,
                    'message' => 'Le mot de passe actuel est obligatoire pour changer le mot de passe',
                ], 400);
            }

            if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                return $this->json([
                    'ok' => false,
                    'message' => 'Le mot de passe actuel est incorrect',
                ], 400);
            }

            $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
        }

        $user->setCompanyName($companyName !== '' ? $companyName : null);
        $user->setCustomerCode($customerCode !== '' ? $customerCode : null);
        $user->setCity($city !== '' ? $city : null);
        $user->setAddress1($address1 !== '' ? $address1 : null);
        $user->setAddress2($address2 !== '' ? $address2 : null);

        $em->flush();

        return $this->json([
            'ok' => true,
            'message' => 'Profil mis à jour avec succès',
            'user' => $this->normalizeUser($user),
        ]);
    }

    private function normalizeUser(User $user): array
    {
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'companyName' => $user->getCompanyName(),
            'customerCode' => $user->getCustomerCode(),
            'address1' => $user->getAddress1(),
            'address2' => $user->getAddress2(),
            'city' => $user->getCity(),
            'isActive' => $user->isActive(),
        ];
    }
}