<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260821104144 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE bet_ai_bankroll (id INT AUTO_INCREMENT NOT NULL, total_balance NUMERIC(10, 2) NOT NULL, initial_balance NUMERIC(10, 2) NOT NULL, currency VARCHAR(3) NOT NULL, last_updated VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE bet_ai_betsuggestion (id INT AUTO_INCREMENT NOT NULL, bet_type VARCHAR(10) NOT NULL, market VARCHAR(255) NOT NULL, prediction VARCHAR(255) NOT NULL, total_odds NUMERIC(10, 2) NOT NULL, suggested_stake NUMERIC(10, 2) NOT NULL, ai_reasoning LONGTEXT NOT NULL, confidence_score INT NOT NULL, is_placed TINYINT NOT NULL, game_week_id INT NOT NULL, INDEX IDX_8D1979685DAD4400 (game_week_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE bet_ai_gameweek (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, start_date VARCHAR(255) NOT NULL, end_date VARCHAR(255) NOT NULL, status VARCHAR(20) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE bet_ai_league (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, country VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE bet_ai_match (id INT AUTO_INCREMENT NOT NULL, raw_home_team_name VARCHAR(255) NOT NULL, raw_away_team_name VARCHAR(255) NOT NULL, match_date VARCHAR(255) NOT NULL, status VARCHAR(20) NOT NULL, result_home INT DEFAULT NULL, result_away INT DEFAULT NULL, result_home_ht INT DEFAULT NULL, result_away_ht INT DEFAULT NULL, game_week_id INT NOT NULL, home_team_id INT NOT NULL, away_team_id INT NOT NULL, INDEX IDX_8FB884EE5DAD4400 (game_week_id), INDEX IDX_8FB884EE9C4C13F6 (home_team_id), INDEX IDX_8FB884EE45185D02 (away_team_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE bet_ai_placedbet (id INT AUTO_INCREMENT NOT NULL, actual_stake NUMERIC(10, 2) NOT NULL, actual_odds NUMERIC(10, 2) NOT NULL, potential_payout NUMERIC(10, 2) NOT NULL, status VARCHAR(20) NOT NULL, actual_payout NUMERIC(10, 2) DEFAULT NULL, placed_at VARCHAR(255) NOT NULL, suggestion_id INT NOT NULL, INDEX IDX_1BCA72B8A41BB822 (suggestion_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE bet_ai_suggestion_match_item (id INT AUTO_INCREMENT NOT NULL, bet_suggestion_id INT NOT NULL, match_id INT NOT NULL, INDEX IDX_A8C737487BFE58E1 (bet_suggestion_id), INDEX IDX_A8C737482ABEACD6 (match_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE bet_ai_team (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, is_active TINYINT NOT NULL, profile_img_url VARCHAR(255) DEFAULT NULL, league_id INT DEFAULT NULL, INDEX IDX_28651A4858AFC4DE (league_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE bet_ai_betsuggestion ADD CONSTRAINT FK_8D1979685DAD4400 FOREIGN KEY (game_week_id) REFERENCES bet_ai_gameweek (id)');
        $this->addSql('ALTER TABLE bet_ai_match ADD CONSTRAINT FK_8FB884EE5DAD4400 FOREIGN KEY (game_week_id) REFERENCES bet_ai_gameweek (id)');
        $this->addSql('ALTER TABLE bet_ai_match ADD CONSTRAINT FK_8FB884EE9C4C13F6 FOREIGN KEY (home_team_id) REFERENCES bet_ai_team (id)');
        $this->addSql('ALTER TABLE bet_ai_match ADD CONSTRAINT FK_8FB884EE45185D02 FOREIGN KEY (away_team_id) REFERENCES bet_ai_team (id)');
        $this->addSql('ALTER TABLE bet_ai_placedbet ADD CONSTRAINT FK_1BCA72B8A41BB822 FOREIGN KEY (suggestion_id) REFERENCES bet_ai_betsuggestion (id)');
        $this->addSql('ALTER TABLE bet_ai_suggestion_match_item ADD CONSTRAINT FK_A8C737487BFE58E1 FOREIGN KEY (bet_suggestion_id) REFERENCES bet_ai_betsuggestion (id)');
        $this->addSql('ALTER TABLE bet_ai_suggestion_match_item ADD CONSTRAINT FK_A8C737482ABEACD6 FOREIGN KEY (match_id) REFERENCES bet_ai_match (id)');
        $this->addSql('ALTER TABLE bet_ai_team ADD CONSTRAINT FK_28651A4858AFC4DE FOREIGN KEY (league_id) REFERENCES bet_ai_league (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bet_ai_betsuggestion DROP FOREIGN KEY FK_8D1979685DAD4400');
        $this->addSql('ALTER TABLE bet_ai_match DROP FOREIGN KEY FK_8FB884EE5DAD4400');
        $this->addSql('ALTER TABLE bet_ai_match DROP FOREIGN KEY FK_8FB884EE9C4C13F6');
        $this->addSql('ALTER TABLE bet_ai_match DROP FOREIGN KEY FK_8FB884EE45185D02');
        $this->addSql('ALTER TABLE bet_ai_placedbet DROP FOREIGN KEY FK_1BCA72B8A41BB822');
        $this->addSql('ALTER TABLE bet_ai_suggestion_match_item DROP FOREIGN KEY FK_A8C737487BFE58E1');
        $this->addSql('ALTER TABLE bet_ai_suggestion_match_item DROP FOREIGN KEY FK_A8C737482ABEACD6');
        $this->addSql('ALTER TABLE bet_ai_team DROP FOREIGN KEY FK_28651A4858AFC4DE');
        $this->addSql('DROP TABLE bet_ai_bankroll');
        $this->addSql('DROP TABLE bet_ai_betsuggestion');
        $this->addSql('DROP TABLE bet_ai_gameweek');
        $this->addSql('DROP TABLE bet_ai_league');
        $this->addSql('DROP TABLE bet_ai_match');
        $this->addSql('DROP TABLE bet_ai_placedbet');
        $this->addSql('DROP TABLE bet_ai_suggestion_match_item');
        $this->addSql('DROP TABLE bet_ai_team');
    }
}
