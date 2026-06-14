<?php

namespace App\Service;

use App\Entity\CustomerOrder;
use App\Entity\Notification;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class NotificationService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $userRepository
    ) {
    }

    public function createNotification(
        User $recipient,
        string $type,
        string $title,
        string $message,
        string $level = Notification::LEVEL_INFO,
        ?User $actor = null,
        ?string $targetType = null,
        ?int $targetId = null,
        array $data = []
    ): Notification {
        $notification = new Notification();
        $notification
            ->setRecipient($recipient)
            ->setActor($actor)
            ->setType($type)
            ->setLevel($level)
            ->setTitle($title)
            ->setMessage($message)
            ->setTargetType($targetType)
            ->setTargetId($targetId)
            ->setData($data);

        $this->em->persist($notification);

        return $notification;
    }

    public function notifyUser(
        User $recipient,
        string $type,
        string $title,
        string $message,
        string $level = Notification::LEVEL_INFO,
        ?User $actor = null,
        ?string $targetType = null,
        ?int $targetId = null,
        array $data = []
    ): void {
        $this->createNotification(
            $recipient,
            $type,
            $title,
            $message,
            $level,
            $actor,
            $targetType,
            $targetId,
            $data
        );

        $this->em->flush();
    }

    public function notifyAdmins(
        string $type,
        string $title,
        string $message,
        string $level = Notification::LEVEL_INFO,
        ?User $actor = null,
        ?string $targetType = null,
        ?int $targetId = null,
        array $data = []
    ): void {
        $admins = $this->userRepository->findAdminUsers();

        foreach ($admins as $admin) {
            if (!$admin instanceof User) {
                continue;
            }

            $this->createNotification(
                $admin,
                $type,
                $title,
                $message,
                $level,
                $actor,
                $targetType,
                $targetId,
                $data
            );
        }

        $this->em->flush();
    }

    public function notifyNewAccountCreated(User $user, ?User $actor = null): void
    {
        $title = 'Nouveau compte créé';
        $message = sprintf(
            'Un nouveau compte client vient d’être créé : %s',
            (string) $user->getEmail()
        );

        $this->notifyAdmins(
            Notification::TYPE_NEW_ACCOUNT,
            $title,
            $message,
            Notification::LEVEL_INFO,
            $actor ?? $user,
            Notification::TARGET_USER,
            $user->getId(),
            [
                'route' => 'admin_clients_index',
                'routeParams' => [],
            ]
        );

        $this->notifyUser(
            $user,
            Notification::TYPE_NEW_ACCOUNT,
            'Compte créé',
            'Votre compte a bien été créé.',
            Notification::LEVEL_SUCCESS,
            $actor ?? $user,
            Notification::TARGET_USER,
            $user->getId(),
            [
                'route' => 'account_profile',
                'routeParams' => [],
            ]
        );
    }

    public function notifyNewOrder(CustomerOrder $order, ?User $actor = null): void
    {
        $customer = $order->getCustomer();
        if (!$customer instanceof User) {
            return;
        }

        $customerName = $order->getCompanyName() ?: $order->getCustomerEmail();

        $this->notifyAdmins(
            Notification::TYPE_NEW_ORDER,
            'Nouvelle commande',
            sprintf(
                'Nouvelle commande %s passée par %s.',
                $order->getOrderNumber(),
                $customerName
            ),
            Notification::LEVEL_SUCCESS,
            $actor ?? $customer,
            Notification::TARGET_ORDER,
            $order->getId(),
            [
                'route' => 'admin_orders_show',
                'routeParams' => ['id' => $order->getId()],
            ]
        );

        $this->notifyUser(
            $customer,
            Notification::TYPE_NEW_ORDER,
            'Commande enregistrée',
            sprintf(
                'Votre commande %s a bien été enregistrée.',
                $order->getOrderNumber()
            ),
            Notification::LEVEL_SUCCESS,
            $actor ?? $customer,
            Notification::TARGET_ORDER,
            $order->getId(),
            [
                'route' => 'order_show',
                'routeParams' => ['id' => $order->getId()],
            ]
        );
    }

    public function notifyOrderStatusChanged(
        CustomerOrder $order,
        string $oldStatus,
        string $newStatus,
        ?User $actor = null
    ): void {
        $customer = $order->getCustomer();
        if (!$customer instanceof User) {
            return;
        }

        $labels = CustomerOrder::getStatusLabels();
        $oldLabel = $labels[$oldStatus] ?? $oldStatus;
        $newLabel = $labels[$newStatus] ?? $newStatus;

        $this->notifyUser(
            $customer,
            Notification::TYPE_ORDER_STATUS_CHANGED,
            'Statut de commande mis à jour',
            sprintf(
                'La commande %s est passée de "%s" à "%s".',
                $order->getOrderNumber(),
                $oldLabel,
                $newLabel
            ),
            Notification::LEVEL_INFO,
            $actor,
            Notification::TARGET_ORDER,
            $order->getId(),
            [
                'route' => 'order_show',
                'routeParams' => ['id' => $order->getId()],
                'oldStatus' => $oldStatus,
                'newStatus' => $newStatus,
            ]
        );
    }

    public function notifyNewComment(
        User $recipient,
        string $title,
        string $message,
        ?User $actor = null,
        ?string $route = null,
        array $routeParams = []
    ): void {
        $data = [];
        if ($route) {
            $data['route'] = $route;
            $data['routeParams'] = $routeParams;
        }

        $this->notifyUser(
            $recipient,
            Notification::TYPE_NEW_COMMENT,
            $title,
            $message,
            Notification::LEVEL_INFO,
            $actor,
            Notification::TARGET_COMMENT,
            null,
            $data
        );
    }

    public function notifyProfileUpdated(User $targetUser, ?User $actor = null, array $changedFields = []): void
    {
        $fieldsText = !empty($changedFields) ? implode(', ', $changedFields) : 'informations du profil';

        // notification pour le user lui-même
        $this->createNotification(
            $targetUser,
            'profile_updated',
            'Profil mis à jour',
            sprintf('Votre profil a été mis à jour (%s).', $fieldsText),
            Notification::LEVEL_SUCCESS,
            $actor,
            Notification::TARGET_USER,
            $targetUser->getId(),
            [
                'route' => 'account_profile',
                'routeParams' => [],
                'changedFields' => $changedFields,
            ]
        );

        // notification pour les admins
        $admins = $this->userRepository->findAdminUsers();
        foreach ($admins as $admin) {
            if (!$admin instanceof User) {
                continue;
            }

            // éviter doublon si le user modifié est lui-même admin
            if ($admin->getId() === $targetUser->getId()) {
                continue;
            }

            $this->createNotification(
                $admin,
                'profile_updated',
                'Profil utilisateur modifié',
                sprintf(
                    'Le profil de %s a été mis à jour (%s).',
                    (string) $targetUser->getEmail(),
                    $fieldsText
                ),
                Notification::LEVEL_INFO,
                $actor,
                Notification::TARGET_USER,
                $targetUser->getId(),
                [
                    'route' => 'admin_clients_index',
                    'routeParams' => [],
                    'changedFields' => $changedFields,
                ]
            );
        }

        $this->em->flush();
    }
}