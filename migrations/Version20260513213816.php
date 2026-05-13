<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260513213816 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE project ADD COLUMN image VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__project AS SELECT id, name, description, icon, route, url FROM project');
        $this->addSql('DROP TABLE project');
        $this->addSql('CREATE TABLE project (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description CLOB NOT NULL, icon VARCHAR(255) NOT NULL, route VARCHAR(255) DEFAULT NULL, url VARCHAR(255) DEFAULT NULL)');
        $this->addSql('INSERT INTO project (id, name, description, icon, route, url) SELECT id, name, description, icon, route, url FROM __temp__project');
        $this->addSql('DROP TABLE __temp__project');
    }
}
