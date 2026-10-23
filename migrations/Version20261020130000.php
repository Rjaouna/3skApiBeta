<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261020130000 extends AbstractMigration
{
    public function getDescription(): string { return 'Stores the public image source configured for the EBP catalog.'; }
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE catalog_image_configuration (id INT AUTO_INCREMENT NOT NULL, base_url VARCHAR(500) DEFAULT NULL, last_tested_at DATETIME DEFAULT NULL, last_error LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }
    public function down(Schema $schema): void { $this->addSql('DROP TABLE catalog_image_configuration'); }
}
