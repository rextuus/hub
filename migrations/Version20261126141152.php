<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261126141152 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Startseite: Projekte mit Video, Bildausschnitt, featured, Position, requiresLogin (BetAI wie bisher nur angemeldet)';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE project ADD video VARCHAR(255) DEFAULT NULL, ADD image_position VARCHAR(32) DEFAULT NULL, ADD featured TINYINT DEFAULT 0 NOT NULL, ADD position INT DEFAULT 0 NOT NULL, ADD requires_login TINYINT DEFAULT 0 NOT NULL');
        // Bisher fest im Controller: BetAI nur für angemeldete Benutzer.
        $this->addSql("UPDATE project SET requires_login = 1 WHERE route = 'app_bet_ai_index'");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE project DROP video, DROP image_position, DROP featured, DROP position, DROP requires_login');
    }
}
