<?php

namespace App\Controller\Admin;

use App\Service\CompanySettingProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/company-setting', name: 'admin_company_setting_')]
#[IsGranted('ROLE_ADMIN')]
class CompanySettingController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(CompanySettingProvider $provider)
    {
        $setting = $provider->get();

        return $this->render('admin/company_setting/index.html.twig', [
            'setting' => $setting,
            'crudToken' => $this->container->get('security.csrf.token_manager')
                ->getToken('company_setting_update')
                ->getValue(),
        ]);
    }

    #[Route('/update', name: 'update', methods: ['POST'])]
    public function update(
        Request $request,
        CompanySettingProvider $provider,
        EntityManagerInterface $em
    ): JsonResponse {
        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            $payload = $request->request->all();
        }

        $token = (string) ($payload['_token'] ?? '');
        if (!$this->isCsrfTokenValid('company_setting_update', $token)) {
            return $this->json([
                'ok' => false,
                'message' => 'Token CSRF invalide.',
            ], 403);
        }

        $companyName = trim((string) ($payload['companyName'] ?? ''));
        $legalName = trim((string) ($payload['legalName'] ?? ''));
        $emailFrom = trim((string) ($payload['emailFrom'] ?? ''));
        $emailFromName = trim((string) ($payload['emailFromName'] ?? ''));
        $replyToEmail = trim((string) ($payload['replyToEmail'] ?? ''));
        $phone = trim((string) ($payload['phone'] ?? ''));
        $address1 = trim((string) ($payload['address1'] ?? ''));
        $address2 = trim((string) ($payload['address2'] ?? ''));
        $city = trim((string) ($payload['city'] ?? ''));
        $postalCode = trim((string) ($payload['postalCode'] ?? ''));
        $country = trim((string) ($payload['country'] ?? ''));
        $website = trim((string) ($payload['website'] ?? ''));
        $notificationEmailsText = trim((string) ($payload['notificationEmails'] ?? ''));

        if ($companyName === '') {
            return $this->json([
                'ok' => false,
                'message' => 'Le nom de la société est obligatoire.',
            ], 422);
        }

        foreach ([
            'emailFrom' => $emailFrom,
            'replyToEmail' => $replyToEmail,
        ] as $label => $value) {
            if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                return $this->json([
                    'ok' => false,
                    'message' => sprintf('Le champ %s contient une adresse email invalide.', $label),
                ], 422);
            }
        }

        $notificationEmails = [];
        if ($notificationEmailsText !== '') {
            $lines = preg_split('/\r\n|\r|\n|;|,/', $notificationEmailsText);
            foreach ($lines as $line) {
                $email = trim((string) $line);
                if ($email === '') {
                    continue;
                }

                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    return $this->json([
                        'ok' => false,
                        'message' => sprintf('Email de notification invalide : %s', $email),
                    ], 422);
                }

                $notificationEmails[] = $email;
            }
        }

        $setting = $provider->get();
        $setting->setCompanyName($companyName);
        $setting->setLegalName($legalName !== '' ? $legalName : null);
        $setting->setEmailFrom($emailFrom !== '' ? $emailFrom : null);
        $setting->setEmailFromName($emailFromName !== '' ? $emailFromName : null);
        $setting->setReplyToEmail($replyToEmail !== '' ? $replyToEmail : null);
        $setting->setPhone($phone !== '' ? $phone : null);
        $setting->setAddress1($address1 !== '' ? $address1 : null);
        $setting->setAddress2($address2 !== '' ? $address2 : null);
        $setting->setCity($city !== '' ? $city : null);
        $setting->setPostalCode($postalCode !== '' ? $postalCode : null);
        $setting->setCountry($country !== '' ? $country : null);
        $setting->setWebsite($website !== '' ? $website : null);
        $setting->setNotificationEmails($notificationEmails);
        $setting->setSendOrderEmailToAdmin((bool) ($payload['sendOrderEmailToAdmin'] ?? false));
        $setting->setSendOrderEmailToCustomer((bool) ($payload['sendOrderEmailToCustomer'] ?? false));
        $setting->touch();

        $em->flush();

        return $this->json([
            'ok' => true,
            'message' => 'Configuration enregistrée avec succès.',
        ]);
    }
}