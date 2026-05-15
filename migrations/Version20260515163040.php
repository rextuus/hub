<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260515163040 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE esc_voting_participant_note ADD has_fireworks TINYINT NOT NULL, ADD has_gadgets TINYINT NOT NULL, ADD has_extra_dancers TINYINT NOT NULL, ADD rating_hot_or_not INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE esc_voting_participant_note DROP has_fireworks, DROP has_gadgets, DROP has_extra_dancers, DROP rating_hot_or_not');
    }
}
