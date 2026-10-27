<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261025000200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Stores the ApiRecept change cursor and the EBP family identifier on imported articles.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE api_reception_connection ADD catalog_revision INT NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE article ADD FamilyId VARCHAR(191) DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_article_family_id ON article (FamilyId)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_article_family_id ON article');
        $this->addSql('ALTER TABLE article DROP FamilyId');
        $this->addSql('ALTER TABLE api_reception_connection DROP catalog_revision');
    }
}
