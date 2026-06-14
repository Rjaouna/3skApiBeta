<?php

namespace App\Controller\Api;

use App\Entity\Notification;
use App\Entity\User;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/notifications', name: 'api_notifications_')]
#[IsGranted('ROLE_USER')]
class NotificationApiController extends AbstractController
{
    #[Route('/latest', name: 'latest', methods: ['GET'])]
    public function latest(NotificationRepository $notificationRepository): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['ok' => false], 403);
        }

        $items = $notificationRepository->findLatestForUser($user, 8);
        $unreadCount = $notificationRepository->countUnreadForUser($user);

        return $this->json([
            'ok' => true,
            'items' => array_map(fn(Notification $n) => $this->normalize($n), $items),
            'unreadCount' => $unreadCount,
        ]);
    }

    #[Route('/unread-count', name: 'unread_count', methods: ['GET'])]
    public function unreadCount(NotificationRepository $notificationRepository): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['ok' => false], 403);
        }

        return $this->json([
            'ok' => true,
            'unreadCount' => $notificationRepository->countUnreadForUser($user),
        ]);
    }

    #[Route('/{id}/read', name: 'read', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function read(
        int $id,
        NotificationRepository $notificationRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['ok' => false], 403);
        }

        $notification = $notificationRepository->findUserNotification($user, $id);
        if (!$notification) {
            return $this->json([
                'ok' => false,
                'message' => 'Notification introuvable.',
            ], 404);
        }

        if (!$notification->isRead()) {
            $notification->markAsRead();
            $em->flush();
        }

        return $this->json([
            'ok' => true,
            'message' => 'Notification marquée comme lue.',
        ]);
    }

   #[Route('/read-all', name: 'read_all', methods: ['POST'])]
    public function readAll(
        NotificationRepository $notificationRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json([
                'ok' => false,
                'message' => 'Non autorisé.',
            ], 403);
        }

        $notifications = $notificationRepository->findBy([
            'recipient' => $user,
            'isRead' => false,
        ]);

        $now = new \DateTimeImmutable();
        $count = 0;

        foreach ($notifications as $notification) {
            if (!$notification instanceof Notification) {
                continue;
            }

            $notification->setIsRead(true);
            $notification->setReadAt($now);
            $count++;
        }

        $em->flush();

        return $this->json([
            'ok' => true,
            'count' => $count,
            'message' => $count > 0
                ? sprintf('%d notification(s) marquée(s) comme lue(s).', $count)
                : 'Aucune notification non lue.',
        ]);
    }

    private function normalize(Notification $notification): array
    {
        return [
            'id' => $notification->getId(),
            'type' => $notification->getType(),
            'level' => $notification->getLevel(),
            'title' => $notification->getTitle(),
            'message' => $notification->getMessage(),
            'isRead' => $notification->isRead(),
            'createdAt' => $notification->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'link' => $this->resolveLink($notification),
        ];
    }

    private function resolveLink(Notification $notification): ?string
    {
        $data = $notification->getData();

        if (!empty($data['route'])) {
            return $this->generateUrl(
                (string) $data['route'],
                is_array($data['routeParams'] ?? null) ? $data['routeParams'] : []
            );
        }

        if ($notification->getTargetType() === Notification::TARGET_ORDER && $notification->getTargetId()) {
            return $this->generateUrl('order_show', ['id' => $notification->getTargetId()]);
        }

        return null;
    }
}