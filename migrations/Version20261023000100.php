<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261023000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds targeted EBP customer imports and a stable EBP customer identifier on users.';
    }

    public function up(Schema $schema): void
    {
        $users = $schema->getTable('user');
        if (!$users->hasColumn('ebp_unique_id')) {
            $this->addSql('ALTER TABLE `user` ADD ebp_unique_id VARCHAR(36) DEFAULT NULL');
        }
        if (!$users->hasIndex('uniq_user_ebp_unique_id')) {
            $this->addSql('CREATE UNIQUE INDEX uniq_user_ebp_unique_id ON `user` (ebp_unique_id)');
        }

        if (!$schema->hasTable('ebp_customer_import')) {
            $this->addSql('CREATE TABLE ebp_customer_import (id INT AUTO_INCREMENT NOT NULL, unique_id VARCHAR(36) NOT NULL, remote_request_id INT DEFAULT NULL, status VARCHAR(16) NOT NULL, message LONGTEXT DEFAULT NULL, customer JSON DEFAULT NULL, user_id INT DEFAULT NULL, requested_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX uniq_ebp_customer_import_unique_id (unique_id), INDEX IDX_CEC5E459A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
            $this->addSql('ALTER TABLE ebp_customer_import ADD CONSTRAINT FK_CEC5E459A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE SET NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ebp_customer_import DROP FOREIGN KEY FK_CEC5E459A76ED395');
        $this->addSql('DROP TABLE ebp_customer_import');
        $this->addSql('DROP INDEX uniq_user_ebp_unique_id ON `user`');
        $this->addSql('ALTER TABLE `user` DROP ebp_unique_id');
    }
}
