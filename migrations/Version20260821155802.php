<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260821155802 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bet_ai_bankroll CHANGE last_updated last_updated DATETIME NOT NULL');
        $this->addSql('ALTER TABLE bet_ai_match CHANGE match_date match_date DATETIME NOT NULL, CHANGE home_team_id home_team_id INT DEFAULT NULL, CHANGE away_team_id away_team_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE bet_ai_placedbet CHANGE placed_at placed_at DATETIME NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bet_ai_bankroll CHANGE last_updated last_updated VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE bet_ai_match CHANGE match_date match_date VARCHAR(255) NOT NULL, CHANGE home_team_id home_team_id INT NOT NULL, CHANGE away_team_id away_team_id INT NOT NULL');
        $this->addSql('ALTER TABLE bet_ai_placedbet CHANGE placed_at placed_at VARCHAR(255) NOT NULL');
    }
}
