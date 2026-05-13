<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260513221443 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE esc_voting_country (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, country_code VARCHAR(10) NOT NULL, start_order INTEGER NOT NULL)');
        $this->addSql('CREATE TABLE esc_voting_vote (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, created_at DATETIME NOT NULL, points INTEGER NOT NULL, session_id VARCHAR(255) DEFAULT NULL, country_id INTEGER NOT NULL, voter_id INTEGER DEFAULT NULL, CONSTRAINT FK_964DF9C0F92F3E70 FOREIGN KEY (country_id) REFERENCES esc_voting_country (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_964DF9C0EBB4B8AD FOREIGN KEY (voter_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_964DF9C0F92F3E70 ON esc_voting_vote (country_id)');
        $this->addSql('CREATE INDEX IDX_964DF9C0EBB4B8AD ON esc_voting_vote (voter_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE esc_voting_country');
        $this->addSql('DROP TABLE esc_voting_vote');
    }
}
