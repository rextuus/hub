<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260515084421 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE esc_voting_edition (id INT AUTO_INCREMENT NOT NULL, year VARCHAR(255) NOT NULL, location VARCHAR(255) NOT NULL, date DATETIME DEFAULT NULL, is_active TINYINT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE esc_voting_ballot ADD edition_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE esc_voting_ballot ADD CONSTRAINT FK_4644611A74281A5E FOREIGN KEY (edition_id) REFERENCES esc_voting_edition (id)');
        $this->addSql('CREATE INDEX IDX_4644611A74281A5E ON esc_voting_ballot (edition_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE esc_voting_edition');
        $this->addSql('ALTER TABLE esc_voting_ballot DROP FOREIGN KEY FK_4644611A74281A5E');
        $this->addSql('DROP INDEX IDX_4644611A74281A5E ON esc_voting_ballot');
        $this->addSql('ALTER TABLE esc_voting_ballot DROP edition_id');
    }
}
