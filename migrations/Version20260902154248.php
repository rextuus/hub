<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260902154248 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bet_ai_suggestion_match_item DROP FOREIGN KEY `FK_A8C737487BFE58E1`');
        $this->addSql('ALTER TABLE bet_ai_suggestion_match_item ADD CONSTRAINT FK_A8C737487BFE58E1 FOREIGN KEY (bet_suggestion_id) REFERENCES bet_ai_betsuggestion (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bet_ai_suggestion_match_item DROP FOREIGN KEY FK_A8C737487BFE58E1');
        $this->addSql('ALTER TABLE bet_ai_suggestion_match_item ADD CONSTRAINT `FK_A8C737487BFE58E1` FOREIGN KEY (bet_suggestion_id) REFERENCES bet_ai_betsuggestion (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
