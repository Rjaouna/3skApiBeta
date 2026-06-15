<?php

namespace App\Controller;

use App\Repository\HeroSlideRepository;
use App\Service\CompanySettingProvider;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home', methods: ['GET'])]
    public function index(
        HeroSlideRepository $heroSlideRepository,
        EntityManagerInterface $entityManager
    ): Response
    {
        $slides = $heroSlideRepository->findForHomepage();

        $catalogStats = $entityManager->getConnection()->fetchAssociative(
            <<<'SQL'
                SELECT
                    COUNT(*) AS total_references,
                    SUM(CASE WHEN RealStock > 0 THEN 1 ELSE 0 END) AS stocked_references,
                    COUNT(DISTINCT NULLIF(xx_Vehicule, '')) AS vehicle_brands,
                    COUNT(DISTINCT NULLIF(xx_Marque, '')) AS product_brands
                FROM article
            SQL
        );

        return $this->render('home/index.html.twig', [
            'slides' => $slides,
            'homeStats' => [
                'totalReferences' => (int) ($catalogStats['total_references'] ?? 0),
                'stockedReferences' => (int) ($catalogStats['stocked_references'] ?? 0),
                'vehicleBrands' => (int) ($catalogStats['vehicle_brands'] ?? 0),
                'productBrands' => (int) ($catalogStats['product_brands'] ?? 0),
            ],
        ]);
    }

    #[Route('/', name: 'home_contact', methods: ['POST'])]
    public function contact(
        Request $request,
        MailerInterface $mailer,
        CompanySettingProvider $companySettingProvider,
        LoggerInterface $logger
    ): Response {
        $redirect = new RedirectResponse($this->generateUrl('home') . '#contact', Response::HTTP_SEE_OTHER);

        if (!$this->isCsrfTokenValid('public_contact', (string) $request->request->get('_token'))) {
            return $this->contactResponse(
                $request,
                $redirect,
                false,
                'Votre session a expiré. Actualisez la page puis renvoyez le formulaire.',
                419
            );
        }

        // Champ invisible : les robots le remplissent souvent, les visiteurs ne le voient pas.
        if (trim((string) $request->request->get('website')) !== '') {
            return $this->contactResponse(
                $request,
                $redirect,
                true,
                'Votre message a bien été envoyé.'
            );
        }

        $name = trim((string) $request->request->get('name'));
        $email = trim((string) $request->request->get('email'));
        $phone = trim((string) $request->request->get('phone'));
        $subject = trim((string) $request->request->get('subject'));
        $message = trim((string) $request->request->get('message'));

        if (
            mb_strlen($name) < 2
            || !filter_var($email, FILTER_VALIDATE_EMAIL)
            || mb_strlen($subject) < 3
            || mb_strlen($message) < 10
            || mb_strlen($name) > 150
            || mb_strlen($email) > 180
            || mb_strlen($phone) > 50
            || mb_strlen($subject) > 180
            || mb_strlen($message) > 5000
        ) {
            return $this->contactResponse(
                $request,
                $redirect,
                false,
                'Merci de vérifier les informations du formulaire de contact.',
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        try {
            $company = $companySettingProvider->get();
            $fromEmail = trim((string) $company->getEmailFrom());

            if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
                $fromEmail = 'commandes@3skpartsauto.com';
            }

            $contactEmail = (new TemplatedEmail())
                ->from(new Address($fromEmail, '3SK Parts Auto'))
                ->to(new Address('contact@3skpartsauto.com', 'Contact 3SK Parts Auto'))
                ->replyTo(new Address($email, $name))
                ->subject('[Contact site] ' . $subject)
                ->htmlTemplate('emails/contact.html.twig')
                ->context([
                    'contactName' => $name,
                    'contactEmail' => $email,
                    'contactPhone' => $phone,
                    'contactSubject' => $subject,
                    'contactMessage' => $message,
                ]);

            $mailer->send($contactEmail);
            return $this->contactResponse(
                $request,
                $redirect,
                true,
                'Votre message a bien été envoyé. Nous vous répondrons rapidement.'
            );
        } catch (\Throwable $exception) {
            $logger->error('Échec de l’envoi du formulaire de contact public.', [
                'message' => $exception->getMessage(),
                'visitorEmail' => $email,
            ]);

            return $this->contactResponse(
                $request,
                $redirect,
                false,
                'Le message n’a pas pu être envoyé. Merci de réessayer dans quelques instants.',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    private function contactResponse(
        Request $request,
        RedirectResponse $redirect,
        bool $success,
        string $message,
        int $status = Response::HTTP_OK
    ): Response {
        if ($request->isXmlHttpRequest() || str_contains((string) $request->headers->get('Accept'), 'application/json')) {
            return $this->json([
                'ok' => $success,
                'message' => $message,
            ], $status);
        }

        $this->addFlash($success ? 'success' : 'danger', $message);
        return $redirect;
    }
}
