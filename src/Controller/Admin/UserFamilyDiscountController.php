<?php

namespace App\Controller\Admin;

use App\Entity\UserFamilyDiscount;
use App\Form\UserFamilyDiscountType;
use App\Repository\ArticleRepository;
use App\Repository\UserFamilyDiscountRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/user-family-discounts', name: 'admin_user_family_discount_')]
#[IsGranted('ROLE_ADMIN')]
class UserFamilyDiscountController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(UserFamilyDiscountRepository $repository): Response
    {
        return $this->render('admin/user_family_discount/index.html.twig', [
            'discounts' => $repository->findAllForAdminList(),
        ]);
    }

    #[Route('/families/search', name: 'family_suggestions', methods: ['GET'])]
    public function familySuggestions(Request $request, ArticleRepository $articleRepository): JsonResponse
    {
        $query = trim((string) $request->query->get('q', ''));

        return $this->json([
            'ok' => true,
            'items' => $articleRepository->searchDistinctFamilyNames($query, 3),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $discount = new UserFamilyDiscount();
        $form = $this->createForm(UserFamilyDiscountType::class, $discount);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $em->persist($discount);
                $em->flush();

                $this->addFlash('success', 'Remise personnelle créée.');

                if ($this->isAjax($request)) {
                    return $this->json([
                        'ok' => true,
                        'message' => 'Remise personnelle créée.',
                        'redirect' => $this->generateUrl('admin_user_family_discount_index'),
                    ]);
                }

                return $this->redirectToRoute('admin_user_family_discount_index');
            }

            if ($this->isAjax($request)) {
                return $this->buildAjaxInvalidFormResponse(
                    $form,
                    'Créer la remise'
                );
            }
        }

        return $this->render('admin/user_family_discount/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(
        UserFamilyDiscount $discount,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $form = $this->createForm(UserFamilyDiscountType::class, $discount);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $discount->touch();
                $em->flush();

                $this->addFlash('success', 'Remise personnelle mise à jour.');

                if ($this->isAjax($request)) {
                    return $this->json([
                        'ok' => true,
                        'message' => 'Remise personnelle mise à jour.',
                        'redirect' => $this->generateUrl('admin_user_family_discount_index'),
                    ]);
                }

                return $this->redirectToRoute('admin_user_family_discount_index');
            }

            if ($this->isAjax($request)) {
                return $this->buildAjaxInvalidFormResponse(
                    $form,
                    'Enregistrer'
                );
            }
        }

        return $this->render('admin/user_family_discount/edit.html.twig', [
            'discount' => $discount,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(
        UserFamilyDiscount $discount,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        if (!$this->isCsrfTokenValid(
            'delete_user_family_discount_' . $discount->getId(),
            (string) $request->request->get('_token')
        )) {
            $message = 'Token invalide.';

            if ($this->isAjax($request)) {
                return $this->json([
                    'ok' => false,
                    'message' => $message,
                ], 400);
            }

            $this->addFlash('danger', $message);

            return $this->redirectToRoute('admin_user_family_discount_index');
        }

        $em->remove($discount);
        $em->flush();

        $this->addFlash('success', 'Remise personnelle supprimée.');

        if ($this->isAjax($request)) {
            return $this->json([
                'ok' => true,
                'message' => 'Remise personnelle supprimée.',
                'redirect' => $this->generateUrl('admin_user_family_discount_index'),
            ]);
        }

        return $this->redirectToRoute('admin_user_family_discount_index');
    }

    private function isAjax(Request $request): bool
    {
        return $request->isXmlHttpRequest()
            || str_contains((string) $request->headers->get('Accept'), 'application/json');
    }

    private function buildAjaxInvalidFormResponse(FormInterface $form, string $submitLabel): JsonResponse
    {
        return $this->json([
            'ok' => false,
            'html' => $this->renderView('admin/user_family_discount/_form.html.twig', [
                'form' => $form->createView(),
                'submit_label' => $submitLabel,
                'cancel_url' => $this->generateUrl('admin_user_family_discount_index'),
            ]),
        ], 422);
    }
}