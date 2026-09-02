<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260902154158 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bet_ai_betsuggestion DROP FOREIGN KEY `FK_8D1979685DAD4400`');
        $this->addSql('ALTER TABLE bet_ai_betsuggestion ADD CONSTRAINT FK_8D1979685DAD4400 FOREIGN KEY (game_week_id) REFERENCES bet_ai_gameweek (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bet_ai_betsuggestion DROP FOREIGN KEY FK_8D1979685DAD4400');
        $this->addSql('ALTER TABLE bet_ai_betsuggestion ADD CONSTRAINT `FK_8D1979685DAD4400` FOREIGN KEY (game_week_id) REFERENCES bet_ai_gameweek (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
