<?php

namespace App\Dto;

/** Immutable, validated input used for the local customer-document dashboard. */
final readonly class CustomerDocumentFilter
{
    public function __construct(
        public string $query = '',
        public ?string $kind = null,
        public ?\DateTimeImmutable $from = null,
        public ?\DateTimeImmutable $to = null,
    ) {}
}
