<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261024000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Keeps the last successful EBP invoice synchronization while a newer request is pending.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ebp_invoice_import ADD last_synced_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ebp_invoice_import DROP last_synced_at');
    }
}
