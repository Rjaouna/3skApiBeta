<?php

namespace App\Service;

use App\Entity\Article;
use App\Exception\ApiReceptionException;

/** Converts the stable ApiRecept item contract into the site's Article projection. */
final class ApiCatalogRecordMapper
{
    /** @var list<string> */
    private const REQUIRED_FIELDS = [
        'Id',
        'UniqueId',
        'RealStock',
        'SalePriceVatIncluded',
        'SalePriceVatExcluded',
        'FamilyId',
    ];

    /**
     * @param array<string, mixed> $record
     *
     * @return array<string, mixed>
     */
    public function normalize(array $record, array $familyNamesById): array
    {
        $data = $record['data'] ?? null;
        if (!is_array($data) || array_is_list($data)) {
            throw new ApiReceptionException('Un article reçu ne contient pas ses données EBP.');
        }

        foreach (self::REQUIRED_FIELDS as $field) {
            if (!array_key_exists($field, $data)) {
                throw new ApiReceptionException(sprintf('Le profil Articles doit exporter le champ « %s ».', $field));
            }
        }

        $uniqueId = $this->text($data['UniqueId'], 36);
        $id = $this->text($data['Id'], 64);
        if ($uniqueId === '' || !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uniqueId)) {
            throw new ApiReceptionException('Un article reçu possède un UniqueId EBP invalide.');
        }
        if ($id === '') {
            throw new ApiReceptionException('Un article reçu ne possède pas de référence Id.');
        }

        $designation = $this->text($data['DesComClear'] ?? '', 255);
        if ($designation === '') {
            $designation = $this->text($data['Caption'] ?? '', 255);
        }

        $familyId = $this->nullableText($data['FamilyId'], 191);
        $familyName = null;
        if ($familyId !== null) {
            $familyName = $familyNamesById[$familyId] ?? null;
            if ($familyName === null) {
                $numericAlias = ApiCatalogFamilyKey::numericAlias($familyId);
                $familyName = $numericAlias === null ? null : ($familyNamesById[$numericAlias] ?? null);
            }
            if ($familyName === null) {
                throw new ApiReceptionException(sprintf('La famille « %s » d’un article est absente de dbo.ItemFamily. Exportez le profil Familles avec « Id » et « Caption », puis relancez son export.', $familyId));
            }
        }

        return [
            'UniqueId' => $uniqueId,
            'Id' => $id,
            'DesComClear' => $designation,
            'RealStock' => $this->integer($data['RealStock'], 'RealStock'),
            'SalePriceVatIncluded' => $this->decimal($data['SalePriceVatIncluded'], 'SalePriceVatIncluded'),
            'SalePriceVatExcluded' => $this->decimal($data['SalePriceVatExcluded'], 'SalePriceVatExcluded'),
            'xx_Position' => $this->nullableText($data['xx_Position'] ?? null, 255),
            'xx_Annee' => $this->nullableText($data['xx_Annee'] ?? null, 255),
            'xx_Vehicule' => $this->nullableText($data['xx_Vehicule'] ?? null, 255),
            'xx_Marque' => $this->nullableText($data['xx_Marque'] ?? null, 100),
            'sysModifiedDate' => $this->date($data['sysModifiedDate'] ?? null, 'sysModifiedDate'),
            'sysCreatedDate' => $this->date($data['sysCreatedDate'] ?? null, 'sysCreatedDate'),
            'FamilyName' => $familyName,
            'ExportStartedAt' => $this->date($data['ExportStartedAt'] ?? null, 'ExportStartedAt'),
            'FtpFinishedAt' => $this->date($data['FtpFinishedAt'] ?? null, 'FtpFinishedAt'),
        ];
    }

    /** @param array<string, mixed> $row */
    public function apply(Article $article, array $row): void
    {
        $article->setId($row['Id']);
        $article->setDesComClear($row['DesComClear']);
        $article->setRealStock($row['RealStock']);
        $article->setSalePriceVatIncluded($row['SalePriceVatIncluded']);
        $article->setSalePriceVatExcluded($row['SalePriceVatExcluded']);
        $article->setXxPosition($row['xx_Position']);
        $article->setXxAnnee($row['xx_Annee']);
        $article->setXxVehicule($row['xx_Vehicule']);
        $article->setXxMarque($row['xx_Marque']);
        $article->setSysModifiedDate($row['sysModifiedDate']);
        $article->setSysCreatedDate($row['sysCreatedDate']);
        $article->setFamilyName($row['FamilyName']);
        $article->setExportStartedAt($row['ExportStartedAt']);
        $article->setFtpFinishedAt($row['FtpFinishedAt']);
    }

    /** @param array<string, mixed> $row */
    public function matches(Article $article, array $row): bool
    {
        return ($article->getId() ?? '') === $row['Id']
            && ($article->getDesComClear() ?? '') === $row['DesComClear']
            && $article->getRealStock() === $row['RealStock']
            && $this->decimal($article->getSalePriceVatIncluded(), 'SalePriceVatIncluded') === $row['SalePriceVatIncluded']
            && $this->decimal($article->getSalePriceVatExcluded(), 'SalePriceVatExcluded') === $row['SalePriceVatExcluded']
            && ($article->getXxPosition() ?? null) === $row['xx_Position']
            && ($article->getXxAnnee() ?? null) === $row['xx_Annee']
            && ($article->getXxVehicule() ?? null) === $row['xx_Vehicule']
            && ($article->getXxMarque() ?? null) === $row['xx_Marque']
            && $this->sameDate($article->getSysModifiedDate(), $row['sysModifiedDate'])
            && $this->sameDate($article->getSysCreatedDate(), $row['sysCreatedDate'])
            && ($article->getFamilyName() ?? null) === $row['FamilyName']
            && $this->sameDate($article->getExportStartedAt(), $row['ExportStartedAt'])
            && $this->sameDate($article->getFtpFinishedAt(), $row['FtpFinishedAt']);
    }

    private function text(mixed $value, int $limit): string
    {
        if (!is_scalar($value) && $value !== null) {
            return '';
        }

        return mb_substr(trim((string) $value), 0, $limit, 'UTF-8');
    }

    private function nullableText(mixed $value, int $limit): ?string
    {
        $value = $this->text($value, $limit);

        return $value === '' ? null : $value;
    }

    private function integer(mixed $value, string $field): int
    {
        if (!is_int($value) && !is_float($value) && !is_string($value)) {
            throw new ApiReceptionException(sprintf('Le champ « %s » d’un article est invalide.', $field));
        }

        $normalized = $this->numericString($value);
        if (!is_numeric($normalized)) {
            throw new ApiReceptionException(sprintf('Le champ « %s » d’un article est invalide.', $field));
        }

        return max(0, (int) $normalized);
    }

    private function decimal(mixed $value, string $field): string
    {
        if (!is_int($value) && !is_float($value) && !is_string($value)) {
            throw new ApiReceptionException(sprintf('Le champ « %s » d’un article est invalide.', $field));
        }

        $normalized = $this->numericString($value);
        if (!is_numeric($normalized)) {
            throw new ApiReceptionException(sprintf('Le champ « %s » d’un article est invalide.', $field));
        }

        return number_format((float) $normalized, 2, '.', '');
    }

    private function numericString(int|float|string $value): string
    {
        $value = str_replace(["\u{00A0}", ' '], '', (string) $value);
        $comma = strrpos($value, ',');
        $dot = strrpos($value, '.');
        if ($comma !== false && $dot !== false) {
            $value = $comma > $dot ? str_replace('.', '', $value) : str_replace(',', '', $value);
        }

        return str_replace(',', '.', $value);
    }

    private function date(mixed $value, string $field): ?\DateTimeImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (!is_scalar($value)) {
            throw new ApiReceptionException(sprintf('La date « %s » d’un article est invalide.', $field));
        }

        $string = trim((string) $value);
        if (preg_match('/^\\/Date\\((-?\d+)/', $string, $matches)) {
            return (new \DateTimeImmutable('@' . (int) ((int) $matches[1] / 1000)))->setTimezone(new \DateTimeZone('UTC'));
        }

        try {
            return new \DateTimeImmutable($string);
        } catch (\Exception) {
            throw new ApiReceptionException(sprintf('La date « %s » d’un article est invalide.', $field));
        }
    }

    private function sameDate(?\DateTimeImmutable $left, ?\DateTimeImmutable $right): bool
    {
        if ($left === null || $right === null) {
            return $left === $right;
        }

        return $left->getTimestamp() === $right->getTimestamp();
    }
}
