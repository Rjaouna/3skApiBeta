<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261023000300 extends AbstractMigration
{
    public function getDescription(): string { return 'Stores EBP invoice import state and customer invoices locally.'; }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE ebp_invoice_import (id INT AUTO_INCREMENT NOT NULL, remote_request_id INT DEFAULT NULL, status VARCHAR(16) NOT NULL, invoice_count INT NOT NULL, message LONGTEXT DEFAULT NULL, requested_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, completed_at DATETIME DEFAULT NULL, user_id INT NOT NULL, UNIQUE INDEX uniq_ebp_invoice_import_user (user_id), INDEX IDX_F0BB7C39A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE customer_invoice (id INT AUTO_INCREMENT NOT NULL, source_id VARCHAR(191) NOT NULL, document_number VARCHAR(191) DEFAULT NULL, document_type VARCHAR(100) DEFAULT NULL, document_date DATETIME DEFAULT NULL, document_state VARCHAR(100) DEFAULT NULL, total_ttc NUMERIC(14, 2) DEFAULT NULL, total_ht NUMERIC(14, 2) DEFAULT NULL, payload JSON NOT NULL, synced_at DATETIME NOT NULL, customer_id INT NOT NULL, UNIQUE INDEX uniq_customer_invoice_source (customer_id, source_id), INDEX idx_customer_invoice_customer_date (customer_id, document_date), INDEX IDX_AAF32A159392CAB (customer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE ebp_invoice_import ADD CONSTRAINT FK_F0BB7C39A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE customer_invoice ADD CONSTRAINT FK_AAF32A159392CAB FOREIGN KEY (customer_id) REFERENCES `user` (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ebp_invoice_import DROP FOREIGN KEY FK_F0BB7C39A76ED395');
        $this->addSql('ALTER TABLE customer_invoice DROP FOREIGN KEY FK_AAF32A159392CAB');
        $this->addSql('DROP TABLE ebp_invoice_import');
        $this->addSql('DROP TABLE customer_invoice');
    }
}
