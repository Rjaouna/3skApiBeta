<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261027000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Stores the date period selected for each EBP document import.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ebp_invoice_import ADD period_from DATE DEFAULT NULL, ADD period_to DATE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ebp_invoice_import DROP period_from, DROP period_to');
    }
}
