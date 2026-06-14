<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260606181418 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE article (UniqueId VARCHAR(36) NOT NULL, Id VARCHAR(64) NOT NULL, DesComClear VARCHAR(255) DEFAULT NULL, RealStock INT DEFAULT 0 NOT NULL, SalePriceVatIncluded NUMERIC(10, 2) DEFAULT \'0.00\' NOT NULL, SalePriceVatExcluded NUMERIC(10, 2) DEFAULT \'0.00\' NOT NULL, xx_Position VARCHAR(255) DEFAULT NULL, xx_Annee VARCHAR(255) DEFAULT NULL, xx_Vehicule VARCHAR(255) DEFAULT NULL, xx_Marque VARCHAR(100) DEFAULT NULL, sysModifiedDate DATETIME DEFAULT NULL, sysCreatedDate DATETIME DEFAULT NULL, FamilyName VARCHAR(100) DEFAULT NULL, ExportStartedAt DATETIME DEFAULT NULL, FtpFinishedAt DATETIME DEFAULT NULL, reduction VARCHAR(10) DEFAULT NULL, UNIQUE INDEX UNIQ_23A0E662ABD43F2 (Id), INDEX idx_family (FamilyName), INDEX idx_marque (xx_Marque), PRIMARY KEY (UniqueId)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE cart (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, user_id INT DEFAULT NULL, INDEX IDX_BA388B7A76ED395 (user_id), INDEX idx_cart_status (status), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE cart_item (id INT AUTO_INCREMENT NOT NULL, quantity INT NOT NULL, unit_price_ttc NUMERIC(10, 2) NOT NULL, cart_id INT NOT NULL, article_unique_id VARCHAR(36) NOT NULL, INDEX IDX_F0FE252739F3D367 (article_unique_id), INDEX idx_cartitem_cart (cart_id), UNIQUE INDEX uniq_cart_article (cart_id, article_unique_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE company_setting (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(50) NOT NULL, company_name VARCHAR(255) NOT NULL, legal_name VARCHAR(255) DEFAULT NULL, email_from VARCHAR(255) DEFAULT NULL, email_from_name VARCHAR(255) DEFAULT NULL, reply_to_email VARCHAR(255) DEFAULT NULL, notification_emails JSON NOT NULL, phone VARCHAR(50) DEFAULT NULL, address1 LONGTEXT DEFAULT NULL, address2 LONGTEXT DEFAULT NULL, city VARCHAR(100) DEFAULT NULL, postal_code VARCHAR(30) DEFAULT NULL, country VARCHAR(100) DEFAULT NULL, website VARCHAR(255) DEFAULT NULL, send_order_email_to_admin TINYINT NOT NULL, send_order_email_to_customer TINYINT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX uniq_company_setting_code (code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE customer_order (id INT AUTO_INCREMENT NOT NULL, order_number VARCHAR(50) NOT NULL, status VARCHAR(30) NOT NULL, customer_email VARCHAR(180) NOT NULL, company_name VARCHAR(255) DEFAULT NULL, customer_code VARCHAR(255) DEFAULT NULL, address1 LONGTEXT DEFAULT NULL, address2 LONGTEXT DEFAULT NULL, city VARCHAR(50) DEFAULT NULL, subtotal_ttc NUMERIC(10, 2) NOT NULL, discount_rate NUMERIC(5, 2) NOT NULL, discount_amount_ttc NUMERIC(10, 2) NOT NULL, total_ttc NUMERIC(10, 2) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, customer_id INT NOT NULL, UNIQUE INDEX UNIQ_3B1CE6A3551F0F81 (order_number), INDEX IDX_3B1CE6A39395C3F3 (customer_id), INDEX idx_customer_order_status (status), INDEX idx_customer_order_created_at (created_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE customer_order_line (id INT AUTO_INCREMENT NOT NULL, article_code VARCHAR(255) NOT NULL, designation LONGTEXT DEFAULT NULL, quantity INT NOT NULL, unit_price_ttc NUMERIC(10, 2) NOT NULL, line_total_ttc NUMERIC(10, 2) NOT NULL, unit_price_before_discount_ttc NUMERIC(10, 2) DEFAULT \'0.00\' NOT NULL, discount_rate NUMERIC(5, 2) DEFAULT NULL, discount_amount_ttc NUMERIC(10, 2) DEFAULT \'0.00\' NOT NULL, line_total_before_discount_ttc NUMERIC(10, 2) DEFAULT \'0.00\' NOT NULL, order_ref_id INT NOT NULL, article_unique_id VARCHAR(36) DEFAULT NULL, INDEX IDX_612C8A63E238517C (order_ref_id), INDEX IDX_612C8A6339F3D367 (article_unique_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE hero_slide (id INT AUTO_INCREMENT NOT NULL, image VARCHAR(255) NOT NULL, title VARCHAR(255) DEFAULT NULL, subtitle LONGTEXT DEFAULT NULL, primary_label VARCHAR(255) DEFAULT NULL, primary_url VARCHAR(255) DEFAULT NULL, secondary_label VARCHAR(255) DEFAULT NULL, secondary_url VARCHAR(255) DEFAULT NULL, enabled TINYINT NOT NULL, created_at DATETIME NOT NULL, pinned_at DATETIME DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE import_job (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(10) NOT NULL, file_path VARCHAR(255) NOT NULL, total_rows INT NOT NULL, processed_rows INT NOT NULL, byte_offset BIGINT NOT NULL, last_message LONGTEXT DEFAULT NULL, started_at DATETIME NOT NULL, finished_at DATETIME DEFAULT NULL, inserted_rows INT NOT NULL, updated_rows INT NOT NULL, skipped_rows INT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE import_source (id INT AUTO_INCREMENT NOT NULL, directory_path VARCHAR(255) NOT NULL, file_name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(80) NOT NULL, level VARCHAR(20) NOT NULL, title VARCHAR(255) NOT NULL, message LONGTEXT NOT NULL, target_type VARCHAR(50) DEFAULT NULL, target_id INT DEFAULT NULL, data JSON NOT NULL, created_at DATETIME NOT NULL, read_at DATETIME DEFAULT NULL, recipient_id INT NOT NULL, actor_id INT DEFAULT NULL, INDEX IDX_BF5476CA10DAF24A (actor_id), INDEX idx_notification_recipient (recipient_id), INDEX idx_notification_read_at (read_at), INDEX idx_notification_created_at (created_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, company_name VARCHAR(255) DEFAULT NULL, customer_code VARCHAR(255) DEFAULT NULL, is_active TINYINT DEFAULT NULL, address1 LONGTEXT DEFAULT NULL, address2 LONGTEXT DEFAULT NULL, city VARCHAR(50) DEFAULT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user_family_discount (id INT AUTO_INCREMENT NOT NULL, family_name VARCHAR(100) NOT NULL, discount_rate NUMERIC(5, 2) DEFAULT \'0.00\' NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX idx_user_family_discount_user (user_id), UNIQUE INDEX uniq_user_family_discount (user_id, family_name), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE cart ADD CONSTRAINT FK_BA388B7A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE cart_item ADD CONSTRAINT FK_F0FE25271AD5CDBF FOREIGN KEY (cart_id) REFERENCES cart (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cart_item ADD CONSTRAINT FK_F0FE252739F3D367 FOREIGN KEY (article_unique_id) REFERENCES article (UniqueId) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE customer_order ADD CONSTRAINT FK_3B1CE6A39395C3F3 FOREIGN KEY (customer_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE customer_order_line ADD CONSTRAINT FK_612C8A63E238517C FOREIGN KEY (order_ref_id) REFERENCES customer_order (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE customer_order_line ADD CONSTRAINT FK_612C8A6339F3D367 FOREIGN KEY (article_unique_id) REFERENCES article (UniqueId) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAE92F8F78 FOREIGN KEY (recipient_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA10DAF24A FOREIGN KEY (actor_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE user_family_discount ADD CONSTRAINT FK_318633F2A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cart DROP FOREIGN KEY FK_BA388B7A76ED395');
        $this->addSql('ALTER TABLE cart_item DROP FOREIGN KEY FK_F0FE25271AD5CDBF');
        $this->addSql('ALTER TABLE cart_item DROP FOREIGN KEY FK_F0FE252739F3D367');
        $this->addSql('ALTER TABLE customer_order DROP FOREIGN KEY FK_3B1CE6A39395C3F3');
        $this->addSql('ALTER TABLE customer_order_line DROP FOREIGN KEY FK_612C8A63E238517C');
        $this->addSql('ALTER TABLE customer_order_line DROP FOREIGN KEY FK_612C8A6339F3D367');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAE92F8F78');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CA10DAF24A');
        $this->addSql('ALTER TABLE user_family_discount DROP FOREIGN KEY FK_318633F2A76ED395');
        $this->addSql('DROP TABLE article');
        $this->addSql('DROP TABLE cart');
        $this->addSql('DROP TABLE cart_item');
        $this->addSql('DROP TABLE company_setting');
        $this->addSql('DROP TABLE customer_order');
        $this->addSql('DROP TABLE customer_order_line');
        $this->addSql('DROP TABLE hero_slide');
        $this->addSql('DROP TABLE import_job');
        $this->addSql('DROP TABLE import_source');
        $this->addSql('DROP TABLE notification');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE user_family_discount');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
