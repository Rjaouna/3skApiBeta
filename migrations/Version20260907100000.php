<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260907100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Use UniqueId as the only article import key and store created articles on import jobs.';
    }

    public function up(Schema $schema): void
    {
        $columns = $this->connection->createSchemaManager()->listTableColumns('import_job');
        if (!array_key_exists('created_articles', $columns)) {
            $this->addSql('ALTER TABLE import_job ADD created_articles JSON DEFAULT NULL');
        }

        $indexes = $this->connection->fetchAllAssociative("SHOW INDEX FROM article WHERE Column_name = 'Id' AND Non_unique = 0 AND Key_name <> 'PRIMARY'");
        foreach ($indexes as $index) {
            $indexName = str_replace('`', '``', (string) $index['Key_name']);
            $this->addSql(sprintf('DROP INDEX `%s` ON article', $indexName));
        }
    }

    public function down(Schema $schema): void
    {
        $columns = $this->connection->createSchemaManager()->listTableColumns('import_job');
        if (array_key_exists('created_articles', $columns)) {
            $this->addSql('ALTER TABLE import_job DROP created_articles');
        }

        $indexes = $this->connection->fetchAllAssociative("SHOW INDEX FROM article WHERE Column_name = 'Id' AND Non_unique = 0 AND Key_name <> 'PRIMARY'");
        if ($indexes === []) {
            $this->addSql('CREATE UNIQUE INDEX UNIQ_23A0E662ABD43F2 ON article (Id)');
        }
    }
}
