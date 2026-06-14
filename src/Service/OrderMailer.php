<?php

namespace App\Service;

use App\Entity\CompanySetting;
use App\Entity\CustomerOrder;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class OrderMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly CompanySettingProvider $companySettingProvider,
        private readonly LoggerInterface $logger
    ) {
    }

    public function sendOrderCreatedEmails(CustomerOrder $order): array
    {
        $report = [
            'admin_sent' => false,
            'customer_sent' => false,
            'errors' => [],
        ];

        try {
            $setting = $this->companySettingProvider->get();
        } catch (\Throwable $e) {
            $this->logger->error('Impossible de charger la configuration société.', [
                'message' => $e->getMessage(),
            ]);

            $report['errors'][] = 'Configuration société introuvable : ' . $e->getMessage();
            return $report;
        }

        $adminEmails = $this->normalizeEmails($setting->getNotificationEmails());
        $customerEmail = trim((string) $order->getCustomerEmail());

        $this->logger->info('Début envoi emails commande', [
            'orderId' => $order->getId(),
            'orderNumber' => $order->getOrderNumber(),
            'adminEnabled' => $setting->isSendOrderEmailToAdmin(),
            'customerEnabled' => $setting->isSendOrderEmailToCustomer(),
            'adminEmails' => $adminEmails,
            'customerEmail' => $customerEmail,
            'from' => $setting->getEmailFrom(),
            'replyTo' => $setting->getReplyToEmail(),
        ]);

        if ($setting->isSendOrderEmailToAdmin()) {
            $report['admin_sent'] = $this->sendAdminNotification($order, $setting, $adminEmails, $report);
        }

        if ($setting->isSendOrderEmailToCustomer()) {
            $report['customer_sent'] = $this->sendCustomerConfirmation($order, $setting, $customerEmail, $report);
        }

        return $report;
    }

    /**
     * @param mixed $rawEmails
     * @return string[]
     */
    private function normalizeEmails(mixed $rawEmails): array
    {
        if (is_string($rawEmails)) {
            $decoded = json_decode($rawEmails, true);
            if (is_array($decoded)) {
                $rawEmails = $decoded;
            } else {
                $rawEmails = preg_split('/[,;]+/', $rawEmails) ?: [];
            }
        }

        if (!is_array($rawEmails)) {
            return [];
        }

        $emails = [];
        foreach ($rawEmails as $email) {
            $email = trim((string) $email);
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $emails[] = mb_strtolower($email);
            }
        }

        return array_values(array_unique($emails));
    }

    private function sendAdminNotification(
        CustomerOrder $order,
        CompanySetting $setting,
        array $emails,
        array &$report
    ): bool {
        if ($emails === []) {
            $this->logger->warning('Aucun email admin valide configuré.', [
                'orderId' => $order->getId(),
            ]);
            $report['errors'][] = 'Aucun email admin valide configuré.';
            return false;
        }

        try {
            $email = (new TemplatedEmail())
                ->from(new Address(
                    $setting->getEmailFrom() ?: 'commandes@3skpartsauto.com',
                    $setting->getEmailFromName() ?: ($setting->getCompanyName() ?: '3SK PARTS AUTO')
                ))
                ->to(...array_map(
                    static fn(string $value) => new Address($value),
                    $emails
                ))
                ->subject('Nouvelle commande ' . (string) $order->getOrderNumber())
                ->htmlTemplate('emails/order_admin.html.twig')
                ->context([
                    'order' => $order,
                    'company' => $setting,
                ]);

            if ($setting->getReplyToEmail()) {
                $replyTo = trim((string) $setting->getReplyToEmail());
                if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
                    $email->replyTo(new Address($replyTo));
                }
            }

            $this->mailer->send($email);

            $this->logger->info('Email admin envoyé.', [
                'orderId' => $order->getId(),
                'to' => $emails,
            ]);

            return true;
        } catch (\Throwable $e) {
            $this->logger->error('Erreur email admin commande', [
                'orderId' => $order->getId(),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $report['errors'][] = 'Erreur envoi email admin : ' . $e->getMessage();
            return false;
        }
    }

    private function sendCustomerConfirmation(
        CustomerOrder $order,
        CompanySetting $setting,
        string $customerEmail,
        array &$report
    ): bool {
        if ($customerEmail === '' || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            $this->logger->warning('Email client invalide ou vide.', [
                'orderId' => $order->getId(),
                'customerEmail' => $customerEmail,
            ]);
            $report['errors'][] = 'Email client invalide ou vide.';
            return false;
        }

        try {
            $email = (new TemplatedEmail())
                ->from(new Address(
                    $setting->getEmailFrom() ?: 'commandes@3skpartsauto.com',
                    $setting->getEmailFromName() ?: ($setting->getCompanyName() ?: '3SK PARTS AUTO')
                ))
                ->to(new Address($customerEmail))
                ->subject('Confirmation de votre commande ' . (string) $order->getOrderNumber())
                ->htmlTemplate('emails/order_customer.html.twig')
                ->context([
                    'order' => $order,
                    'company' => $setting,
                ]);

            if ($setting->getReplyToEmail()) {
                $replyTo = trim((string) $setting->getReplyToEmail());
                if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
                    $email->replyTo(new Address($replyTo));
                }
            }

            $this->mailer->send($email);

            $this->logger->info('Email client envoyé.', [
                'orderId' => $order->getId(),
                'to' => $customerEmail,
            ]);

            return true;
        } catch (\Throwable $e) {
            $this->logger->error('Erreur email client commande', [
                'orderId' => $order->getId(),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $report['errors'][] = 'Erreur envoi email client : ' . $e->getMessage();
            return false;
        }
    }
}