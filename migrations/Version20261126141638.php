<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261126141638 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fotokalender: Kalender, Monatsblätter, Fotos';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE fotokalender_calendar (id INT AUTO_INCREMENT NOT NULL, year INT NOT NULL, title VARCHAR(120) NOT NULL, intro LONGTEXT DEFAULT NULL, published TINYINT DEFAULT 1 NOT NULL, UNIQUE INDEX UNIQ_F374A43BBB827337 (year), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE fotokalender_month (id INT AUTO_INCREMENT NOT NULL, month SMALLINT NOT NULL, motto VARCHAR(120) NOT NULL, cover VARCHAR(255) DEFAULT NULL, calendar_id INT NOT NULL, UNIQUE INDEX uniq_fotokalender_month (calendar_id, month), INDEX IDX_A260DCC0A40A2C8 (calendar_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE fotokalender_photo (id INT AUTO_INCREMENT NOT NULL, image VARCHAR(255) DEFAULT NULL, caption VARCHAR(255) DEFAULT NULL, position INT DEFAULT 0 NOT NULL, month_id INT NOT NULL, INDEX IDX_386148DEA0CBDE4 (month_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE fotokalender_month ADD CONSTRAINT FK_A260DCC0A40A2C8 FOREIGN KEY (calendar_id) REFERENCES fotokalender_calendar (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE fotokalender_photo ADD CONSTRAINT FK_386148DEA0CBDE4 FOREIGN KEY (month_id) REFERENCES fotokalender_month (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE fotokalender_month DROP FOREIGN KEY FK_A260DCC0A40A2C8');
        $this->addSql('ALTER TABLE fotokalender_photo DROP FOREIGN KEY FK_386148DEA0CBDE4');
        $this->addSql('DROP TABLE fotokalender_calendar');
        $this->addSql('DROP TABLE fotokalender_month');
        $this->addSql('DROP TABLE fotokalender_photo');
    }
}
