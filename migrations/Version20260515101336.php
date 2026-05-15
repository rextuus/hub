<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260515101336 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE esc_voting_participant (id INT AUTO_INCREMENT NOT NULL, artist VARCHAR(255) NOT NULL, song VARCHAR(255) NOT NULL, start_order INT NOT NULL, country_id INT NOT NULL, edition_id INT NOT NULL, INDEX IDX_E4830B12F92F3E70 (country_id), INDEX IDX_E4830B1274281A5E (edition_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE esc_voting_participant ADD CONSTRAINT FK_E4830B12F92F3E70 FOREIGN KEY (country_id) REFERENCES esc_voting_country (id)');
        $this->addSql('ALTER TABLE esc_voting_participant ADD CONSTRAINT FK_E4830B1274281A5E FOREIGN KEY (edition_id) REFERENCES esc_voting_edition (id)');
        $this->addSql('ALTER TABLE esc_voting_country DROP start_order');
        $this->addSql('ALTER TABLE esc_voting_vote DROP FOREIGN KEY `FK_964DF9C0F92F3E70`');
        $this->addSql('DROP INDEX IDX_964DF9C0F92F3E70 ON esc_voting_vote');
        $this->addSql('ALTER TABLE esc_voting_vote CHANGE country_id participant_id INT NOT NULL');
        $this->addSql('ALTER TABLE esc_voting_vote ADD CONSTRAINT FK_964DF9C09D1C3019 FOREIGN KEY (participant_id) REFERENCES esc_voting_participant (id)');
        $this->addSql('CREATE INDEX IDX_964DF9C09D1C3019 ON esc_voting_vote (participant_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE esc_voting_participant DROP FOREIGN KEY FK_E4830B12F92F3E70');
        $this->addSql('ALTER TABLE esc_voting_participant DROP FOREIGN KEY FK_E4830B1274281A5E');
        $this->addSql('DROP TABLE esc_voting_participant');
        $this->addSql('ALTER TABLE esc_voting_country ADD start_order INT NOT NULL');
        $this->addSql('ALTER TABLE esc_voting_vote DROP FOREIGN KEY FK_964DF9C09D1C3019');
        $this->addSql('DROP INDEX IDX_964DF9C09D1C3019 ON esc_voting_vote');
        $this->addSql('ALTER TABLE esc_voting_vote CHANGE participant_id country_id INT NOT NULL');
        $this->addSql('ALTER TABLE esc_voting_vote ADD CONSTRAINT `FK_964DF9C0F92F3E70` FOREIGN KEY (country_id) REFERENCES esc_voting_country (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_964DF9C0F92F3E70 ON esc_voting_vote (country_id)');
    }
}
