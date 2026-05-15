<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260515135012 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE esc_voting_ballot DROP FOREIGN KEY `FK_4644611AEBB4B8AD`');
        $this->addSql('ALTER TABLE esc_voting_ballot ADD CONSTRAINT FK_4644611AEBB4B8AD FOREIGN KEY (voter_id) REFERENCES esc_voting_voter (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE esc_voting_participant_note DROP FOREIGN KEY `FK_86D85ADDEBB4B8AD`');
        $this->addSql('ALTER TABLE esc_voting_participant_note ADD CONSTRAINT FK_86D85ADDEBB4B8AD FOREIGN KEY (voter_id) REFERENCES esc_voting_voter (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE esc_voting_vote DROP FOREIGN KEY `FK_964DF9C0EBB4B8AD`');
        $this->addSql('ALTER TABLE esc_voting_vote ADD CONSTRAINT FK_964DF9C0EBB4B8AD FOREIGN KEY (voter_id) REFERENCES esc_voting_voter (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE esc_voting_ballot DROP FOREIGN KEY FK_4644611AEBB4B8AD');
        $this->addSql('ALTER TABLE esc_voting_ballot ADD CONSTRAINT `FK_4644611AEBB4B8AD` FOREIGN KEY (voter_id) REFERENCES esc_voting_voter (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE esc_voting_participant_note DROP FOREIGN KEY FK_86D85ADDEBB4B8AD');
        $this->addSql('ALTER TABLE esc_voting_participant_note ADD CONSTRAINT `FK_86D85ADDEBB4B8AD` FOREIGN KEY (voter_id) REFERENCES esc_voting_voter (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE esc_voting_vote DROP FOREIGN KEY FK_964DF9C0EBB4B8AD');
        $this->addSql('ALTER TABLE esc_voting_vote ADD CONSTRAINT `FK_964DF9C0EBB4B8AD` FOREIGN KEY (voter_id) REFERENCES esc_voting_voter (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
