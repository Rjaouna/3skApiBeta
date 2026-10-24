<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261023000200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds creation and update timestamps to customer accounts.';
    }

    public function up(Schema $schema): void
    {
        $users = $schema->getTable('user');
        if (!$users->hasColumn('created_at')) {
            $this->addSql('ALTER TABLE `user` ADD created_at DATETIME DEFAULT NULL');
        }
        if (!$users->hasColumn('updated_at')) {
            $this->addSql('ALTER TABLE `user` ADD updated_at DATETIME DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` DROP created_at');
        $this->addSql('ALTER TABLE `user` DROP updated_at');
    }
}
