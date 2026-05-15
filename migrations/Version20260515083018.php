<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260515083018 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE esc_voting_ballot (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, voter_id INT NOT NULL, INDEX IDX_4644611AEBB4B8AD (voter_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE esc_voting_country (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, country_code VARCHAR(10) NOT NULL, start_order INT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE esc_voting_vote (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, points INT NOT NULL, session_id VARCHAR(255) DEFAULT NULL, country_id INT NOT NULL, voter_id INT DEFAULT NULL, ballot_id INT DEFAULT NULL, INDEX IDX_964DF9C0F92F3E70 (country_id), INDEX IDX_964DF9C0EBB4B8AD (voter_id), INDEX IDX_964DF9C0DDC23F6C (ballot_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE esc_voting_voter (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) DEFAULT NULL, session_id VARCHAR(255) DEFAULT NULL, user_id INT DEFAULT NULL, INDEX IDX_F7FB70D4A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE project (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, icon VARCHAR(255) NOT NULL, route VARCHAR(255) DEFAULT NULL, url VARCHAR(255) DEFAULT NULL, image VARCHAR(255) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE esc_voting_ballot ADD CONSTRAINT FK_4644611AEBB4B8AD FOREIGN KEY (voter_id) REFERENCES esc_voting_voter (id)');
        $this->addSql('ALTER TABLE esc_voting_vote ADD CONSTRAINT FK_964DF9C0F92F3E70 FOREIGN KEY (country_id) REFERENCES esc_voting_country (id)');
        $this->addSql('ALTER TABLE esc_voting_vote ADD CONSTRAINT FK_964DF9C0EBB4B8AD FOREIGN KEY (voter_id) REFERENCES esc_voting_voter (id)');
        $this->addSql('ALTER TABLE esc_voting_vote ADD CONSTRAINT FK_964DF9C0DDC23F6C FOREIGN KEY (ballot_id) REFERENCES esc_voting_ballot (id)');
        $this->addSql('ALTER TABLE esc_voting_voter ADD CONSTRAINT FK_F7FB70D4A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE esc_voting_ballot DROP FOREIGN KEY FK_4644611AEBB4B8AD');
        $this->addSql('ALTER TABLE esc_voting_vote DROP FOREIGN KEY FK_964DF9C0F92F3E70');
        $this->addSql('ALTER TABLE esc_voting_vote DROP FOREIGN KEY FK_964DF9C0EBB4B8AD');
        $this->addSql('ALTER TABLE esc_voting_vote DROP FOREIGN KEY FK_964DF9C0DDC23F6C');
        $this->addSql('ALTER TABLE esc_voting_voter DROP FOREIGN KEY FK_F7FB70D4A76ED395');
        $this->addSql('DROP TABLE esc_voting_ballot');
        $this->addSql('DROP TABLE esc_voting_country');
        $this->addSql('DROP TABLE esc_voting_vote');
        $this->addSql('DROP TABLE esc_voting_voter');
        $this->addSql('DROP TABLE project');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
