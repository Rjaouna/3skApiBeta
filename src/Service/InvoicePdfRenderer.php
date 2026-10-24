<?php

namespace App\Service;

use App\Entity\CustomerInvoice;

/**
 * Dependency-free PDF renderer for customer invoices.
 *
 * The original EBP document is not available to the website. This service renders
 * the imported header and line data as a clear customer copy without pretending to
 * reproduce EBP's legal layout or fields that were not transmitted.
 */
final class InvoicePdfRenderer
{
    public function render(CustomerInvoice $invoice): string
    {
        $pdf = new InvoicePdfCanvas();
        $pageNumber = 0;
        $y = 0.0;

        $openPage = function (bool $continuation = false) use (&$pdf, &$pageNumber, &$y, $invoice): void {
            $pdf->beginPage();
            ++$pageNumber;
            $reference = $invoice->getDocumentNumber() ?: $invoice->getSourceId();
            $documentLabel = $invoice->getDocumentLabel();
            $pdf->text(42, 800, '3SK PARTS AUTO', 18, true, [49, 95, 183]);
            $pdf->text(42, 782, 'Copie numérique issue des données EBP', 8, false, [92, 101, 115]);
            $pdf->text(438, 800, $continuation ? mb_strtoupper($documentLabel) . ' — SUITE' : mb_strtoupper($documentLabel), 15, true, [255, 138, 0]);
            $pdf->text(438, 783, 'N° ' . $reference, 9, true, [35, 43, 56]);
            $pdf->line(42, 766, 553, 766, [207, 214, 225]);

            if ($continuation) {
                $pdf->text(42, 740, 'Détail des lignes — ' . mb_strtolower($documentLabel) . ' ' . $reference, 10, true, [35, 43, 56]);
                $y = 716;

                return;
            }

            $pdf->text(42, 739, 'FACTURÉ À', 8, true, [49, 95, 183]);
            $billing = $invoice->getBilling();
            $name = $billing['companyName'] ?: $billing['email'] ?: 'Client';
            $customerLines = array_values(array_filter([
                $name,
                $billing['customerCode'] ? 'Code client : ' . $billing['customerCode'] : null,
                $billing['address1'],
                $billing['address2'],
                $billing['city'],
                $billing['country'],
                $billing['email'],
                $billing['phone'] ? 'Tél. : ' . $billing['phone'] : null,
            ], static fn (?string $line): bool => $line !== null && trim($line) !== ''));
            $lineY = 722.0;
            foreach ($customerLines as $line) {
                $wrappedLines = InvoicePdfCanvas::wrap($line, 48);
                if (count($wrappedLines) > 3) {
                    $wrappedLines = array_slice($wrappedLines, 0, 3);
                    $wrappedLines[2] = rtrim($wrappedLines[2], '…') . '…';
                }
                foreach ($wrappedLines as $wrapped) {
                    $pdf->text(42, $lineY, $wrapped, 9, $line === $name, [35, 43, 56]);
                    $lineY -= 13;
                }
            }

            $pdf->rect(340, 688, 213, 58, [245, 246, 248], true);
            $pdf->text(353, 728, 'Date de facture', 8, false, [92, 101, 115]);
            $pdf->text(457, 728, self::date($invoice->getDocumentDate()), 9, true, [35, 43, 56]);
            $pdf->text(353, 709, 'État EBP', 8, false, [92, 101, 115]);
            $pdf->text(457, 709, $invoice->getDocumentState() ?: '—', 9, true, [35, 43, 56]);
            if ($invoice->getDueDate() !== null) {
                $pdf->text(353, 690, 'Échéance', 8, false, [92, 101, 115]);
                $pdf->text(457, 690, self::date($invoice->getDueDate()), 9, true, [35, 43, 56]);
            }
            // Place le tableau sous le plus long des deux blocs d'en-tête.
            $y = min(660.0, $lineY - 10.0);
        };

        $drawHeader = function () use (&$pdf, &$y): void {
            $pdf->rect(42, $y - 18, 511, 18, [245, 246, 248], true);
            $pdf->text(48, $y - 12, 'RÉFÉRENCE', 7, true, [35, 43, 56]);
            $pdf->text(141, $y - 12, 'DÉSIGNATION', 7, true, [35, 43, 56]);
            $pdf->text(373, $y - 12, 'QTÉ', 7, true, [35, 43, 56]);
            $pdf->text(414, $y - 12, 'PU HT', 7, true, [35, 43, 56]);
            $pdf->text(493, $y - 12, 'TOTAL HT', 7, true, [35, 43, 56]);
            $y -= 18;
        };

        $openPage();
        $drawHeader();
        foreach ($invoice->getLines() as $line) {
            $reference = self::lineText($line, 'itemId', '—');
            $designation = self::lineText($line, 'designation', '—');
            $descriptionLines = InvoicePdfCanvas::wrap($designation, 40);
            $rowHeight = max(22, count($descriptionLines) * 11 + 8);
            if ($y - $rowHeight < 140) {
                $openPage(true);
                $drawHeader();
            }
            $top = $y;
            $pdf->text(48, $top - 14, $reference, 8, false, [35, 43, 56]);
            foreach ($descriptionLines as $index => $descriptionLine) {
                $pdf->text(141, $top - 14 - ($index * 11), $descriptionLine, 8, false, [35, 43, 56]);
            }
            $pdf->text(373, $top - 14, self::lineText($line, 'quantity', '—'), 8, false, [35, 43, 56]);
            $pdf->textRight(481, $top - 14, self::amount($line['unitPriceHt'] ?? null), 8, false, [35, 43, 56]);
            $pdf->textRight(547, $top - 14, self::amount($line['totalHt'] ?? null), 8, false, [35, 43, 56]);
            $pdf->line(42, $top - $rowHeight, 553, $top - $rowHeight, [229, 233, 239]);
            $y -= $rowHeight;
        }

        if ($y < 170) {
            $openPage(true);
        }
        $totalsY = $y - 14;
        $pdf->line(347, $totalsY + 13, 553, $totalsY + 13, [207, 214, 225]);
        $pdf->text(390, $totalsY, 'Total HT', 9, false, [92, 101, 115]);
        $pdf->textRight(547, $totalsY, self::amount($invoice->getTotalHt()), 9, true, [35, 43, 56]);
        if ($invoice->getTotalVat() !== null) {
            $totalsY -= 18;
            $pdf->text(390, $totalsY, 'TVA', 9, false, [92, 101, 115]);
            $pdf->textRight(547, $totalsY, self::amount($invoice->getTotalVat()), 9, true, [35, 43, 56]);
        }
        $totalsY -= 22;
        $pdf->rect(347, $totalsY - 8, 206, 25, [49, 95, 183], true);
        $pdf->text(359, $totalsY + 1, 'TOTAL TTC', 10, true, [255, 255, 255]);
        $pdf->textRight(541, $totalsY + 1, self::amount($invoice->getTotalTtc()), 11, true, [255, 255, 255]);
        $pdf->text(42, 67, 'Document généré depuis les données transmises par EBP le ' . (new \DateTimeImmutable())->format('d/m/Y à H:i') . '.', 7, false, [92, 101, 115]);
        $pdf->text(42, 52, 'Pour toute question sur cette facture, contactez votre interlocuteur commercial.', 7, false, [92, 101, 115]);

        return $pdf->output();
    }

    private static function lineText(array $line, string $field, string $fallback): string
    {
        $value = $line[$field] ?? null;

        return is_scalar($value) && trim((string) $value) !== '' ? trim((string) $value) : $fallback;
    }

    private static function amount(mixed $value): string
    {
        if (!is_scalar($value) || !is_numeric((string) $value)) {
            return '—';
        }

        return number_format((float) $value, 2, ',', ' ') . ' DH';
    }

    private static function date(?\DateTimeImmutable $date): string
    {
        return $date?->format('d/m/Y') ?? '—';
    }
}

/** @internal Minimal PDF writer using PDF base fonts; keeps the invoice export deployable without a binary dependency. */
final class InvoicePdfCanvas
{
    /** @var list<string> */
    private array $pages = [];
    private string $content = '';
    private bool $pageOpen = false;

    public function beginPage(): void
    {
        if ($this->pageOpen) {
            $this->pages[] = $this->content;
        }
        $this->content = '';
        $this->pageOpen = true;
    }

    /** @param array{int, int, int} $color */
    public function text(float $x, float $y, string $value, float $size, bool $bold, array $color): void
    {
        $font = $bold ? 'F2' : 'F1';
        $this->content .= sprintf("q %.3F %.3F %.3F rg BT /%s %.2F Tf 1 0 0 1 %.2F %.2F Tm (%s) Tj ET Q\n", $color[0] / 255, $color[1] / 255, $color[2] / 255, $font, $size, $x, $y, $this->escape($value));
    }

    /** @param array{int, int, int} $color */
    public function textRight(float $right, float $y, string $value, float $size, bool $bold, array $color): void
    {
        $this->text(max(42, $right - (strlen($this->pdfText($value)) * ($size * .48))), $y, $value, $size, $bold, $color);
    }

    /** @param array{int, int, int} $color */
    public function line(float $x1, float $y1, float $x2, float $y2, array $color): void
    {
        $this->content .= sprintf("q %.3F %.3F %.3F RG %.2F w %.2F %.2F m %.2F %.2F l S Q\n", $color[0] / 255, $color[1] / 255, $color[2] / 255, .5, $x1, $y1, $x2, $y2);
    }

    /** @param array{int, int, int} $color */
    public function rect(float $x, float $y, float $width, float $height, array $color, bool $fill): void
    {
        $operator = $fill ? 'f' : 'S';
        $colourOperator = $fill ? 'rg' : 'RG';
        $this->content .= sprintf("q %.3F %.3F %.3F %s %.2F %.2F %.2F %.2F re %s Q\n", $color[0] / 255, $color[1] / 255, $color[2] / 255, $colourOperator, $x, $y, $width, $height, $operator);
    }

    /** @return list<string> */
    public static function wrap(string $value, int $maxCharacters): array
    {
        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');
        if ($value === '') {
            return ['—'];
        }
        $words = preg_split('/\s+/u', $value) ?: [];
        $lines = [];
        $line = '';
        foreach ($words as $word) {
            $candidate = $line === '' ? $word : $line . ' ' . $word;
            if (mb_strlen($candidate) <= $maxCharacters) {
                $line = $candidate;
                continue;
            }
            if ($line !== '') {
                $lines[] = $line;
            }
            while (mb_strlen($word) > $maxCharacters) {
                $lines[] = mb_substr($word, 0, $maxCharacters - 1) . '…';
                $word = mb_substr($word, $maxCharacters - 1);
            }
            $line = $word;
        }
        if ($line !== '') {
            $lines[] = $line;
        }

        return $lines;
    }

    public function output(): string
    {
        if ($this->pageOpen) {
            $this->pages[] = $this->content;
            $this->pageOpen = false;
        }
        if ($this->pages === []) {
            $this->pages[] = '';
        }

        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',
        ];
        $kids = [];
        foreach ($this->pages as $index => $content) {
            $pageObject = 5 + ($index * 2);
            $streamObject = $pageObject + 1;
            $kids[] = $pageObject . ' 0 R';
            $objects[$pageObject] = sprintf('<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents %d 0 R >>', $streamObject);
            $objects[$streamObject] = sprintf("<< /Length %d >>\nstream\n%sendstream", strlen($content), $content);
        }
        $objects[2] = sprintf('<< /Type /Pages /Kids [%s] /Count %d >>', implode(' ', $kids), count($this->pages));
        ksort($objects);

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0 => 0];
        $last = max(array_keys($objects));
        for ($number = 1; $number <= $last; ++$number) {
            $offsets[$number] = strlen($pdf);
            $pdf .= sprintf("%d 0 obj\n%s\nendobj\n", $number, $objects[$number]);
        }
        $xref = strlen($pdf);
        $pdf .= sprintf("xref\n0 %d\n0000000000 65535 f \n", $last + 1);
        for ($number = 1; $number <= $last; ++$number) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$number]);
        }
        $pdf .= sprintf("trailer\n<< /Size %d /Root 1 0 R >>\nstartxref\n%d\n%%%%EOF\n", $last + 1, $xref);

        return $pdf;
    }

    private function escape(string $value): string
    {
        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', '', ''], $this->pdfText($value));
    }

    private function pdfText(string $value): string
    {
        $converted = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $value);

        return $converted === false ? '' : $converted;
    }
}
