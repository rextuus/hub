<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261126142719 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adventskalender: Kalender, Türen, Antworten, Gewinnstufen, Teilnehmer, gegebene Antworten';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE advent_answer (id INT AUTO_INCREMENT NOT NULL, correct TINYINT NOT NULL, points SMALLINT NOT NULL, answered_at DATETIME NOT NULL, participant_id INT NOT NULL, door_id INT NOT NULL, choice_id INT DEFAULT NULL, UNIQUE INDEX uniq_advent_answer (participant_id, door_id), INDEX IDX_D425F22E9D1C3019 (participant_id), INDEX IDX_D425F22E58639EAE (door_id), INDEX IDX_D425F22E998666D1 (choice_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE advent_calendar (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(120) NOT NULL, year INT NOT NULL, start_date DATE NOT NULL, timezone VARCHAR(64) DEFAULT \'Europe/Berlin\' NOT NULL, intro LONGTEXT DEFAULT NULL, host VARCHAR(190) DEFAULT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_48E74DCBCF2713FD (host), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE advent_choice (id INT AUTO_INCREMENT NOT NULL, position INT DEFAULT 0 NOT NULL, text VARCHAR(500) NOT NULL, correct TINYINT DEFAULT 0 NOT NULL, response LONGTEXT DEFAULT NULL, response_image VARCHAR(255) DEFAULT NULL, response_video_url VARCHAR(255) DEFAULT NULL, door_id INT NOT NULL, INDEX IDX_CF53E29958639EAE (door_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE advent_door (id INT AUTO_INCREMENT NOT NULL, number SMALLINT NOT NULL, question LONGTEXT NOT NULL, image VARCHAR(255) DEFAULT NULL, points SMALLINT DEFAULT 1 NOT NULL, calendar_id INT NOT NULL, UNIQUE INDEX uniq_advent_door_number (calendar_id, number), INDEX IDX_63BBE3DAA40A2C8 (calendar_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE advent_participant (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(80) NOT NULL, token VARCHAR(32) NOT NULL, created_at DATETIME NOT NULL, last_seen_at DATETIME DEFAULT NULL, calendar_id INT NOT NULL, UNIQUE INDEX UNIQ_5DDD33935F37A13B (token), INDEX IDX_5DDD3393A40A2C8 (calendar_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE advent_win_stage (id INT AUTO_INCREMENT NOT NULL, min_points SMALLINT NOT NULL, label VARCHAR(80) NOT NULL, prize VARCHAR(255) NOT NULL, calendar_id INT NOT NULL, INDEX IDX_C4F59BDA40A2C8 (calendar_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE advent_answer ADD CONSTRAINT FK_D425F22E9D1C3019 FOREIGN KEY (participant_id) REFERENCES advent_participant (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE advent_answer ADD CONSTRAINT FK_D425F22E58639EAE FOREIGN KEY (door_id) REFERENCES advent_door (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE advent_answer ADD CONSTRAINT FK_D425F22E998666D1 FOREIGN KEY (choice_id) REFERENCES advent_choice (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE advent_choice ADD CONSTRAINT FK_CF53E29958639EAE FOREIGN KEY (door_id) REFERENCES advent_door (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE advent_door ADD CONSTRAINT FK_63BBE3DAA40A2C8 FOREIGN KEY (calendar_id) REFERENCES advent_calendar (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE advent_participant ADD CONSTRAINT FK_5DDD3393A40A2C8 FOREIGN KEY (calendar_id) REFERENCES advent_calendar (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE advent_win_stage ADD CONSTRAINT FK_C4F59BDA40A2C8 FOREIGN KEY (calendar_id) REFERENCES advent_calendar (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE advent_answer DROP FOREIGN KEY FK_D425F22E9D1C3019');
        $this->addSql('ALTER TABLE advent_answer DROP FOREIGN KEY FK_D425F22E58639EAE');
        $this->addSql('ALTER TABLE advent_answer DROP FOREIGN KEY FK_D425F22E998666D1');
        $this->addSql('ALTER TABLE advent_choice DROP FOREIGN KEY FK_CF53E29958639EAE');
        $this->addSql('ALTER TABLE advent_door DROP FOREIGN KEY FK_63BBE3DAA40A2C8');
        $this->addSql('ALTER TABLE advent_participant DROP FOREIGN KEY FK_5DDD3393A40A2C8');
        $this->addSql('ALTER TABLE advent_win_stage DROP FOREIGN KEY FK_C4F59BDA40A2C8');
        $this->addSql('DROP TABLE advent_answer');
        $this->addSql('DROP TABLE advent_calendar');
        $this->addSql('DROP TABLE advent_choice');
        $this->addSql('DROP TABLE advent_door');
        $this->addSql('DROP TABLE advent_participant');
        $this->addSql('DROP TABLE advent_win_stage');
    }
}
