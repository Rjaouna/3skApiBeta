<?php

namespace App\Service;

/** Normalizes only numeric EBP family identifiers for a safe fallback lookup. */
final class ApiCatalogFamilyKey
{
    public static function numericAlias(string $value): ?string
    {
        if (preg_match('/^[0-9]+$/D', $value) !== 1) {
            return null;
        }

        return "\0numeric-family:" . (ltrim($value, '0') ?: '0');
    }
}
