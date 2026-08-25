<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260821105848 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bet_ai_gameweek CHANGE start_date start_date DATETIME NOT NULL, CHANGE end_date end_date DATETIME NOT NULL');
        $this->addSql('ALTER TABLE bet_ai_league ADD tier INT NOT NULL');
        $this->addSql('ALTER TABLE bet_ai_team ADD wikipedia_url VARCHAR(255) DEFAULT NULL, ADD logo_search_url VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bet_ai_gameweek CHANGE start_date start_date VARCHAR(255) NOT NULL, CHANGE end_date end_date VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE bet_ai_league DROP tier');
        $this->addSql('ALTER TABLE bet_ai_team DROP wikipedia_url, DROP logo_search_url');
    }
}
