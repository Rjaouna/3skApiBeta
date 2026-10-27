<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261025000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Stores resumable state for interactive ApiRecept catalog synchronization.';
    }

    public function up(Schema $schema): void
    {
        // La table contient déjà l'historique de synchronisations : on peuple
        // les lignes existantes avant de rendre la colonne obligatoire.
        $this->addSql("ALTER TABLE api_catalog_sync_run ADD progress_data JSON DEFAULT NULL COMMENT '(DC2Type:json)'");
        $this->addSql("UPDATE api_catalog_sync_run SET progress_data = JSON_OBJECT() WHERE progress_data IS NULL");
        $this->addSql("ALTER TABLE api_catalog_sync_run MODIFY progress_data JSON NOT NULL COMMENT '(DC2Type:json)'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE api_catalog_sync_run DROP progress_data');
    }
}
