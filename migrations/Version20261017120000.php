<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261017120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store administrator category reductions independently from imported articles.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE category_reduction (id INT AUTO_INCREMENT NOT NULL, family_name VARCHAR(100) NOT NULL, discount_rate NUMERIC(5, 2) DEFAULT '0.00' NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX uniq_category_reduction_family (family_name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4");

        // Preserve existing category-wide reductions before imports stop managing Article.reduction.
        $this->addSql(<<<'SQL'
            INSERT INTO category_reduction (family_name, discount_rate, created_at, updated_at)
            SELECT legacy.family_name, legacy.discount_rate, NOW(), NOW()
            FROM (
                SELECT
                    UPPER(TRIM(FamilyName)) AS family_name,
                    CAST(REPLACE(MIN(NULLIF(TRIM(reduction), '')), ',', '.') AS DECIMAL(5, 2)) AS discount_rate
                FROM article
                WHERE FamilyName IS NOT NULL
                  AND TRIM(FamilyName) <> ''
                GROUP BY UPPER(TRIM(FamilyName))
                HAVING COUNT(*) = COUNT(NULLIF(TRIM(reduction), ''))
                   AND COUNT(DISTINCT CAST(REPLACE(NULLIF(TRIM(reduction), ''), ',', '.') AS DECIMAL(5, 2))) = 1
            ) AS legacy
        SQL);

        $this->addSql(<<<'SQL'
            UPDATE article AS article
            INNER JOIN category_reduction AS reduction
                ON UPPER(TRIM(article.FamilyName)) = reduction.family_name
            SET article.reduction = NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            UPDATE article AS article
            INNER JOIN category_reduction AS reduction
                ON UPPER(TRIM(article.FamilyName)) = reduction.family_name
            SET article.reduction = CAST(reduction.discount_rate AS CHAR)
        SQL);
        $this->addSql('DROP TABLE category_reduction');
    }
}
