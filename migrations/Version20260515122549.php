<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260515122549 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE esc_voting_participant_note (id INT AUTO_INCREMENT NOT NULL, rating_song INT DEFAULT NULL, rating_performance INT DEFAULT NULL, rating_voice INT DEFAULT NULL, rating_outfit INT DEFAULT NULL, note LONGTEXT DEFAULT NULL, is_missed TINYINT NOT NULL, voter_id INT NOT NULL, participant_id INT NOT NULL, INDEX IDX_86D85ADDEBB4B8AD (voter_id), INDEX IDX_86D85ADD9D1C3019 (participant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE esc_voting_participant_note ADD CONSTRAINT FK_86D85ADDEBB4B8AD FOREIGN KEY (voter_id) REFERENCES esc_voting_voter (id)');
        $this->addSql('ALTER TABLE esc_voting_participant_note ADD CONSTRAINT FK_86D85ADD9D1C3019 FOREIGN KEY (participant_id) REFERENCES esc_voting_participant (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE esc_voting_participant_note DROP FOREIGN KEY FK_86D85ADDEBB4B8AD');
        $this->addSql('ALTER TABLE esc_voting_participant_note DROP FOREIGN KEY FK_86D85ADD9D1C3019');
        $this->addSql('DROP TABLE esc_voting_participant_note');
    }
}
