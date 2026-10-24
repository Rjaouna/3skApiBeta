<?php

namespace App\Controller;

use App\Dto\CustomerDocumentFilter;
use App\Entity\CustomerInvoice;
use App\Entity\User;
use App\Exception\ApiReceptionException;
use App\Repository\CustomerInvoiceRepository;
use App\Service\EbpInvoiceImportService;
use App\Service\InvoicePdfRenderer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class InvoiceController extends AbstractController
{
    private const DOCUMENTS_PER_PAGE = 10;
    private const DOCUMENT_KINDS = ['invoice', 'credit_note', 'delivery_note'];

    public function __construct(private readonly CsrfTokenManagerInterface $csrf) {}

    #[Route('/mes-factures', name: 'invoice_index', methods: ['GET'])]
    public function index(Request $request, CustomerInvoiceRepository $invoices, EbpInvoiceImportService $imports): Response
    {
        $user = $this->user();
        $filters = $this->filters($request);
        $listing = $this->listingData($filters, $request, $invoices, $user);

        return $this->render('invoice/index.html.twig', [
            'listing' => $listing,
            'filters' => $filters,
            'statistics' => $invoices->statisticsForCustomer($user, $filters),
            'queryParameters' => $this->queryParameters($filters),
            'sync' => $imports->status($user),
            'syncToken' => $this->csrf->getToken('customer_invoice_sync')->getValue(),
        ]);
    }

    #[Route('/mes-factures/liste', name: 'invoice_list', methods: ['GET'])]
    public function listing(Request $request, CustomerInvoiceRepository $invoices): JsonResponse
    {
        $user = $this->user();
        $filters = $this->filters($request);
        $listing = $this->listingData($filters, $request, $invoices, $user);
        $queryParameters = $this->queryParameters($filters);

        return $this->json([
            'ok' => true,
            'html' => $this->renderView('invoice/_list.html.twig', ['listing' => $listing, 'filters' => $filters, 'queryParameters' => $queryParameters]),
            'statisticsHtml' => $this->renderView('invoice/_summary.html.twig', ['statistics' => $invoices->statisticsForCustomer($user, $filters)]),
            'total' => $listing['total'],
            'page' => $listing['page'],
        ], 200, ['Cache-Control' => 'no-store']);
    }

    #[Route('/mes-factures/refresh', name: 'invoice_refresh', methods: ['POST'])]
    public function refresh(Request $request, EbpInvoiceImportService $imports): JsonResponse
    {
        if (!$this->validToken($request)) return $this->forbidden();
        try {
            $imports->request($this->user());
            $summary = $imports->refresh();
        } catch (\DomainException $exception) {
            return $this->json(['ok' => false, 'message' => $exception->getMessage()], 422, ['Cache-Control' => 'no-store']);
        } catch (ApiReceptionException $exception) {
            return $this->json(['ok' => false, 'message' => $exception->getMessage()], 502, ['Cache-Control' => 'no-store']);
        }

        return $this->json([
            'ok' => true,
            'status' => $imports->status($this->user()),
            'summary' => $summary,
            'message' => 'La mise à jour des factures, avoirs et bons de livraison a été demandée à EBP.',
        ], 202, ['Cache-Control' => 'no-store']);
    }

    #[Route('/mes-factures/status', name: 'invoice_status', methods: ['GET'])]
    public function status(EbpInvoiceImportService $imports, CustomerInvoiceRepository $invoices): JsonResponse
    {
        try {
            $imports->refresh();
        } catch (ApiReceptionException $exception) {
            return $this->json(['ok' => false, 'message' => $exception->getMessage()], 502, ['Cache-Control' => 'no-store']);
        }
        $user = $this->user();

        return $this->json([
            'ok' => true,
            'status' => $imports->status($user),
            'documentCount' => count($invoices->findForCustomer($user)),
        ], 200, ['Cache-Control' => 'no-store']);
    }

    #[Route('/mes-factures/{id}', name: 'invoice_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(CustomerInvoice $invoice): Response
    {
        $this->assertCanRead($invoice);

        return $this->render('invoice/show.html.twig', ['invoice' => $invoice]);
    }

    #[Route('/mes-factures/{id}/pdf', name: 'invoice_pdf', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function pdf(CustomerInvoice $invoice, InvoicePdfRenderer $renderer): Response
    {
        $this->assertCanRead($invoice);
        $filename = preg_replace('/[^A-Za-z0-9._-]+/', '-', $invoice->getDocumentNumber() ?: 'document-' . $invoice->getId()) ?: 'document';

        return new Response($renderer->render($invoice), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="%s.pdf"', trim($filename, '-')),
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    private function user(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) throw $this->createAccessDeniedException();

        return $user;
    }

    private function validToken(Request $request): bool
    {
        $payload = json_decode((string) $request->getContent(), true);

        return $this->isCsrfTokenValid('customer_invoice_sync', (string) ($payload['_token'] ?? ''));
    }

    private function forbidden(): JsonResponse
    {
        return $this->json(['ok' => false, 'message' => 'Requête refusée. Rechargez la page.'], 403, ['Cache-Control' => 'no-store']);
    }

    /** @return array{items: list<CustomerInvoice>, total: int, page: int, perPage: int, pages: int, from: int, to: int} */
    private function listingData(CustomerDocumentFilter $filters, Request $request, CustomerInvoiceRepository $invoices, User $user): array
    {
        return $invoices->paginateForCustomer($user, $filters, $this->page($request), self::DOCUMENTS_PER_PAGE);
    }

    private function filters(Request $request): CustomerDocumentFilter
    {
        $query = mb_substr(trim((string) $request->query->get('q', '')), 0, 120);
        $rawKind = $request->query->get('type', '');
        $kind = is_string($rawKind) && in_array($rawKind, self::DOCUMENT_KINDS, true) ? $rawKind : null;
        $from = $this->dateParameter($request, 'from', false);
        $to = $this->dateParameter($request, 'to', true);
        if ($from !== null && $to !== null && $from > $to) {
            [$from, $to] = [$to->setTime(0, 0), $from->setTime(23, 59, 59)];
        }

        return new CustomerDocumentFilter($query, $kind, $from, $to);
    }

    /** @return array<string, string> */
    private function queryParameters(CustomerDocumentFilter $filters): array
    {
        $parameters = [];
        if ($filters->query !== '') $parameters['q'] = $filters->query;
        if ($filters->kind !== null) $parameters['type'] = $filters->kind;
        if ($filters->from !== null) $parameters['from'] = $filters->from->format('Y-m-d');
        if ($filters->to !== null) $parameters['to'] = $filters->to->format('Y-m-d');

        return $parameters;
    }

    private function dateParameter(Request $request, string $name, bool $endOfDay): ?\DateTimeImmutable
    {
        $value = $request->query->get($name, '');
        if (!is_string($value) || preg_match('/^\d{4}-\d{2}-\d{2}$/D', $value) !== 1) {
            return null;
        }
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value, new \DateTimeZone('UTC'));
        $errors = \DateTimeImmutable::getLastErrors();
        if (!$date instanceof \DateTimeImmutable || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) || $date->format('Y-m-d') !== $value) {
            return null;
        }

        return $endOfDay ? $date->setTime(23, 59, 59) : $date;
    }

    private function page(Request $request): int
    {
        $value = $request->query->get('page', '1');

        return is_scalar($value) && ctype_digit((string) $value) ? max(1, (int) $value) : 1;
    }

    private function assertCanRead(CustomerInvoice $invoice): void
    {
        if ($invoice->getCustomer()->getId() !== $this->user()->getId() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }
    }
}
