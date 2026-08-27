<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260827175058 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bet_ai_betsuggestion ADD market_type VARCHAR(255) NOT NULL');

        // Populate existing data
        $this->addSql("UPDATE bet_ai_betsuggestion SET market_type = 'THREE_WAY_COMBINED' WHERE market LIKE '%3-Weg-Wette (Kombiniert)%'");
        $this->addSql("UPDATE bet_ai_betsuggestion SET market_type = 'THREE_WAY' WHERE market LIKE '%3-Weg-Wette%' AND market_type = ''");
        $this->addSql("UPDATE bet_ai_betsuggestion SET market_type = 'HANDICAP' WHERE market LIKE '%Handicap%'");
        $this->addSql("UPDATE bet_ai_betsuggestion SET market_type = 'WIN_OVER_UNDER' WHERE market LIKE '%Sieg &%'");
        $this->addSql("UPDATE bet_ai_betsuggestion SET market_type = 'UNKNOWN' WHERE market_type = ''");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bet_ai_betsuggestion DROP market_type');
    }
}
