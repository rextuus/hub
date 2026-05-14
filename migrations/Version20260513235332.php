<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260513235332 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE esc_voting_ballot (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, created_at DATETIME NOT NULL, voter_id INTEGER NOT NULL, CONSTRAINT FK_4644611AEBB4B8AD FOREIGN KEY (voter_id) REFERENCES esc_voting_voter (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_4644611AEBB4B8AD ON esc_voting_ballot (voter_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__esc_voting_vote AS SELECT id, created_at, points, session_id, country_id, voter_id FROM esc_voting_vote');
        $this->addSql('DROP TABLE esc_voting_vote');
        $this->addSql('CREATE TABLE esc_voting_vote (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, created_at DATETIME NOT NULL, points INTEGER NOT NULL, session_id VARCHAR(255) DEFAULT NULL, country_id INTEGER NOT NULL, voter_id INTEGER DEFAULT NULL, ballot_id INTEGER DEFAULT NULL, CONSTRAINT FK_964DF9C0F92F3E70 FOREIGN KEY (country_id) REFERENCES esc_voting_country (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_964DF9C0EBB4B8AD FOREIGN KEY (voter_id) REFERENCES esc_voting_voter (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_964DF9C0DDC23F6C FOREIGN KEY (ballot_id) REFERENCES esc_voting_ballot (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO esc_voting_vote (id, created_at, points, session_id, country_id, voter_id) SELECT id, created_at, points, session_id, country_id, voter_id FROM __temp__esc_voting_vote');
        $this->addSql('DROP TABLE __temp__esc_voting_vote');
        $this->addSql('CREATE INDEX IDX_964DF9C0F92F3E70 ON esc_voting_vote (country_id)');
        $this->addSql('CREATE INDEX IDX_964DF9C0EBB4B8AD ON esc_voting_vote (voter_id)');
        $this->addSql('CREATE INDEX IDX_964DF9C0DDC23F6C ON esc_voting_vote (ballot_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE esc_voting_ballot');
        $this->addSql('CREATE TEMPORARY TABLE __temp__esc_voting_vote AS SELECT id, created_at, points, session_id, country_id, voter_id FROM esc_voting_vote');
        $this->addSql('DROP TABLE esc_voting_vote');
        $this->addSql('CREATE TABLE esc_voting_vote (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, created_at DATETIME NOT NULL, points INTEGER NOT NULL, session_id VARCHAR(255) DEFAULT NULL, country_id INTEGER NOT NULL, voter_id INTEGER DEFAULT NULL, CONSTRAINT FK_964DF9C0F92F3E70 FOREIGN KEY (country_id) REFERENCES esc_voting_country (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_964DF9C0EBB4B8AD FOREIGN KEY (voter_id) REFERENCES esc_voting_voter (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO esc_voting_vote (id, created_at, points, session_id, country_id, voter_id) SELECT id, created_at, points, session_id, country_id, voter_id FROM __temp__esc_voting_vote');
        $this->addSql('DROP TABLE __temp__esc_voting_vote');
        $this->addSql('CREATE INDEX IDX_964DF9C0F92F3E70 ON esc_voting_vote (country_id)');
        $this->addSql('CREATE INDEX IDX_964DF9C0EBB4B8AD ON esc_voting_vote (voter_id)');
    }
}
