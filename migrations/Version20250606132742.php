<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250606132742 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE device (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(50) NOT NULL, serial_number VARCHAR(255) NOT NULL, fabrication_date DATE NOT NULL, renewal_date DATE NOT NULL, status VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE device_assignment (id INT AUTO_INCREMENT NOT NULL, device_id INT NOT NULL, user_id INT NOT NULL, assigned_date DATE NOT NULL, returned_date DATE DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_B7F6077C94A4C7D4 (device_id), INDEX IDX_B7F6077CA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE device_software (id INT AUTO_INCREMENT NOT NULL, device_id INT NOT NULL, software_id INT NOT NULL, installation_date DATE NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_F9E431EF94A4C7D4 (device_id), INDEX IDX_F9E431EFD7452741 (software_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE incident (id INT AUTO_INCREMENT NOT NULL, device_id INT NOT NULL, reported_by INT DEFAULT NULL, description LONGTEXT NOT NULL, status VARCHAR(50) NOT NULL, report_date DATE NOT NULL, tracking_id VARCHAR(100) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_3D03A11A94A4C7D4 (device_id), INDEX IDX_3D03A11A144F5BA4 (reported_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE software (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, version VARCHAR(50) DEFAULT NULL, license_expiry DATE DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, user_image VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE device_assignment ADD CONSTRAINT FK_B7F6077C94A4C7D4 FOREIGN KEY (device_id) REFERENCES device (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE device_assignment ADD CONSTRAINT FK_B7F6077CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE device_software ADD CONSTRAINT FK_F9E431EF94A4C7D4 FOREIGN KEY (device_id) REFERENCES device (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE device_software ADD CONSTRAINT FK_F9E431EFD7452741 FOREIGN KEY (software_id) REFERENCES software (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE incident ADD CONSTRAINT FK_3D03A11A94A4C7D4 FOREIGN KEY (device_id) REFERENCES device (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE incident ADD CONSTRAINT FK_3D03A11A144F5BA4 FOREIGN KEY (reported_by) REFERENCES user (id) ON DELETE SET NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE device_assignment DROP FOREIGN KEY FK_B7F6077C94A4C7D4
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE device_assignment DROP FOREIGN KEY FK_B7F6077CA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE device_software DROP FOREIGN KEY FK_F9E431EF94A4C7D4
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE device_software DROP FOREIGN KEY FK_F9E431EFD7452741
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE incident DROP FOREIGN KEY FK_3D03A11A94A4C7D4
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE incident DROP FOREIGN KEY FK_3D03A11A144F5BA4
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE device
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE device_assignment
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE device_software
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE incident
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE software
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user
        SQL);
    }
}
