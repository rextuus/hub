<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260515135313 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE esc_voting_vote DROP FOREIGN KEY `FK_964DF9C0DDC23F6C`');
        $this->addSql('ALTER TABLE esc_voting_vote ADD CONSTRAINT FK_964DF9C0DDC23F6C FOREIGN KEY (ballot_id) REFERENCES esc_voting_ballot (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE esc_voting_vote DROP FOREIGN KEY FK_964DF9C0DDC23F6C');
        $this->addSql('ALTER TABLE esc_voting_vote ADD CONSTRAINT `FK_964DF9C0DDC23F6C` FOREIGN KEY (ballot_id) REFERENCES esc_voting_ballot (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
