<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261024000200 extends AbstractMigration
{
    public function getDescription(): string { return 'Tracks the automated SiteWeb to EBP order transfer independently from commercial order statuses.'; }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE customer_order ADD api_reception_order_id INT DEFAULT NULL, ADD ebp_transfer_status VARCHAR(24) NOT NULL DEFAULT 'not_requested', ADD ebp_transfer_message LONGTEXT DEFAULT NULL, ADD ebp_transfer_updated_at DATETIME DEFAULT NULL, ADD ebp_available_at DATETIME DEFAULT NULL");
        $this->addSql('CREATE INDEX idx_customer_order_ebp_transfer ON customer_order (ebp_transfer_status, api_reception_order_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_customer_order_ebp_transfer ON customer_order');
        $this->addSql('ALTER TABLE customer_order DROP api_reception_order_id, DROP ebp_transfer_status, DROP ebp_transfer_message, DROP ebp_transfer_updated_at, DROP ebp_available_at');
    }
}
