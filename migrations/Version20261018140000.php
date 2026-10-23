<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261018140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Stores the encrypted ApiRecept connection and catalog synchronization history.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE api_reception_connection (id INT AUTO_INCREMENT NOT NULL, base_url VARCHAR(500) DEFAULT NULL, api_key_ciphertext LONGTEXT DEFAULT NULL, is_enabled TINYINT(1) NOT NULL, sync_interval_minutes INT NOT NULL, last_tested_at DATETIME DEFAULT NULL, last_synced_at DATETIME DEFAULT NULL, last_error LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE api_catalog_sync_run (id INT AUTO_INCREMENT NOT NULL, connection_id INT NOT NULL, status VARCHAR(12) NOT NULL, remote_total INT NOT NULL, received_rows INT NOT NULL, created_rows INT NOT NULL, updated_rows INT NOT NULL, unchanged_rows INT NOT NULL, removed_rows INT NOT NULL, message LONGTEXT DEFAULT NULL, started_at DATETIME NOT NULL, finished_at DATETIME DEFAULT NULL, INDEX IDX_API_CATALOG_SYNC_CONNECTION (connection_id), INDEX idx_api_catalog_sync_run_started (started_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE api_catalog_sync_run ADD CONSTRAINT FK_API_CATALOG_SYNC_CONNECTION FOREIGN KEY (connection_id) REFERENCES api_reception_connection (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE api_catalog_sync_run DROP FOREIGN KEY FK_API_CATALOG_SYNC_CONNECTION');
        $this->addSql('DROP TABLE api_catalog_sync_run');
        $this->addSql('DROP TABLE api_reception_connection');
    }
}
