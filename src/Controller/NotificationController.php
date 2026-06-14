<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\User;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/mes-notifications', name: 'notification_')]
#[IsGranted('ROLE_USER')]
class NotificationController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        Request $request,
        NotificationRepository $notificationRepository,
        UrlGeneratorInterface $urlGenerator
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 20;
        $filter = (string) $request->query->get('filter', '');
        $onlyUnread = $filter === 'unread' ? true : null;

        $items = $notificationRepository->findByUserPaginated($user, $page, $limit, $onlyUnread);
        $total = $notificationRepository->countByUser($user, $onlyUnread);
        $unreadCount = $notificationRepository->countUnreadForUser($user);

        $notificationUrls = [];
        foreach ($items as $notification) {
            $data = $notification->getData() ?? [];
            $route = $data['route'] ?? null;
            $routeParams = $data['routeParams'] ?? [];

            $url = null;
            if ($route) {
                try {
                    $url = $urlGenerator->generate($route, $routeParams);
                } catch (\Throwable $e) {
                    $url = null;
                }
            }

            $notificationUrls[$notification->getId()] = $url;
        }

        return $this->render('notification/index.html.twig', [
            'notifications' => $items,
            'page' => $page,
            'pages' => max(1, (int) ceil($total / $limit)),
            'total' => $total,
            'unreadCount' => $unreadCount,
            'filter' => $filter,
            'notificationUrls' => $notificationUrls,
        ]);
    }

    #[Route('/dropdown', name: 'dropdown', methods: ['GET'])]
    public function dropdown(
        NotificationRepository $notificationRepository,
        UrlGeneratorInterface $urlGenerator
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['ok' => false], 403);
        }

        $notifications = $notificationRepository->findByUserPaginated($user, 1, 8, true);

        $items = [];
        foreach ($notifications as $notification) {
            $data = $notification->getData() ?? [];
            $route = $data['route'] ?? null;
            $routeParams = $data['routeParams'] ?? [];

            $url = null;
            if ($route) {
                try {
                    $url = $urlGenerator->generate($route, $routeParams);
                } catch (\Throwable $e) {
                    $url = null;
                }
            }

            $items[] = [
                'id' => $notification->getId(),
                'title' => $notification->getTitle(),
                'message' => $notification->getMessage(),
                'level' => $notification->getLevel(),
                'createdAt' => $notification->getCreatedAt()?->format('d/m/Y H:i'),
                'url' => $url,
            ];
        }

        return $this->json([
            'ok' => true,
            'count' => $notificationRepository->countUnreadForUser($user),
            'items' => $items,
        ]);
    }

    #[Route('/tout-lire', name: 'read_all', methods: ['POST'])]
    public function readAll(
        NotificationRepository $notificationRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['ok' => false], 403);
        }

        $notifications = $notificationRepository->findByUserPaginated($user, 1, 500, true);
        $now = new \DateTimeImmutable();

        foreach ($notifications as $notification) {
            if ($notification->getReadAt() === null) {
                $notification->setReadAt($now);
            }
        }

        $em->flush();

        return $this->json([
            'ok' => true,
            'message' => 'Toutes les notifications ont été marquées comme lues.',
        ]);
    }

    #[Route('/{id}/lire', name: 'read', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function read(
        Notification $notification,
        EntityManagerInterface $em
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['ok' => false], 403);
        }

        if ($notification->getRecipient()?->getId() !== $user->getId()) {
            return $this->json(['ok' => false], 403);
        }

        if ($notification->getReadAt() === null) {
            $notification->setReadAt(new \DateTimeImmutable());
            $em->flush();
        }

        return $this->json(['ok' => true]);
    }
}