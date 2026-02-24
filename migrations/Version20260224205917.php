<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260224205917 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE profile');
        $this->addSql('ALTER TABLE medecin ADD CONSTRAINT FK_1BDA53C6CCFA12B8 FOREIGN KEY (profile_id) REFERENCES profil (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_1BDA53C6CCFA12B8 ON medecin (profile_id)');
        $this->addSql('ALTER TABLE patient ADD CONSTRAINT FK_1ADAD7EBCCFA12B8 FOREIGN KEY (profile_id) REFERENCES profil (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_1ADAD7EBCCFA12B8 ON patient (profile_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE profile (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, prenom VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, telephone VARCHAR(8) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, email VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, sexe VARCHAR(6) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, adresse VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, role VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATE DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE medecin DROP FOREIGN KEY FK_1BDA53C6CCFA12B8');
        $this->addSql('DROP INDEX IDX_1BDA53C6CCFA12B8 ON medecin');
        $this->addSql('ALTER TABLE patient DROP FOREIGN KEY FK_1ADAD7EBCCFA12B8');
        $this->addSql('DROP INDEX IDX_1ADAD7EBCCFA12B8 ON patient');
    }
}
