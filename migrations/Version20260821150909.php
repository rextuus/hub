<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260821150909 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE bet_ai_ai_response (id INT AUTO_INCREMENT NOT NULL, raw_response LONGTEXT NOT NULL, has_valid_data TINYINT NOT NULL, created_at DATETIME NOT NULL, game_week_id INT NOT NULL, INDEX IDX_4F4848CE5DAD4400 (game_week_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE bet_ai_ai_response ADD CONSTRAINT FK_4F4848CE5DAD4400 FOREIGN KEY (game_week_id) REFERENCES bet_ai_gameweek (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bet_ai_ai_response DROP FOREIGN KEY FK_4F4848CE5DAD4400');
        $this->addSql('DROP TABLE bet_ai_ai_response');
    }
}
