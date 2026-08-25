<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260821212329 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE bet_ai_dictionary (id INT AUTO_INCREMENT NOT NULL, raw_name VARCHAR(255) NOT NULL, team_id INT NOT NULL, UNIQUE INDEX UNIQ_A16357B17595CAAC (raw_name), INDEX IDX_A16357B1296CD8AE (team_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE bet_ai_transaction (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(10) NOT NULL, amount NUMERIC(10, 2) NOT NULL, description VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, bankroll_id INT NOT NULL, placed_bet_id INT DEFAULT NULL, INDEX IDX_1CE2C084725DA5D8 (bankroll_id), INDEX IDX_1CE2C084CD296375 (placed_bet_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE bet_ai_dictionary ADD CONSTRAINT FK_A16357B1296CD8AE FOREIGN KEY (team_id) REFERENCES bet_ai_team (id)');
        $this->addSql('ALTER TABLE bet_ai_transaction ADD CONSTRAINT FK_1CE2C084725DA5D8 FOREIGN KEY (bankroll_id) REFERENCES bet_ai_bankroll (id)');
        $this->addSql('ALTER TABLE bet_ai_transaction ADD CONSTRAINT FK_1CE2C084CD296375 FOREIGN KEY (placed_bet_id) REFERENCES bet_ai_placedbet (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bet_ai_dictionary DROP FOREIGN KEY FK_A16357B1296CD8AE');
        $this->addSql('ALTER TABLE bet_ai_transaction DROP FOREIGN KEY FK_1CE2C084725DA5D8');
        $this->addSql('ALTER TABLE bet_ai_transaction DROP FOREIGN KEY FK_1CE2C084CD296375');
        $this->addSql('DROP TABLE bet_ai_dictionary');
        $this->addSql('DROP TABLE bet_ai_transaction');
    }
}
