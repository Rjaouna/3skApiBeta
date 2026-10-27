<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261024000300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Stores the immutable country, VAT rate and pre-discount net price required by the EBP order CSV importer.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE customer_order ADD billing_country_code VARCHAR(2) NOT NULL DEFAULT 'MA'");
        $this->addSql('ALTER TABLE customer_order_line ADD unit_price_before_discount_ht NUMERIC(10, 2) DEFAULT NULL, ADD vat_rate NUMERIC(5, 2) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE customer_order_line DROP unit_price_before_discount_ht, DROP vat_rate');
        $this->addSql('ALTER TABLE customer_order DROP billing_country_code');
    }
}
