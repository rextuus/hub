<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Gemeinsamer Medienordner: Team-Logos liegen jetzt unter bet_ai/logos/ im Volume hub_media_data und werden über
 * /media/… bzw. imgproxy ausgeliefert statt direkt unter /assets/bet_ai/logos/.
 * Die Dateien im Volume zieht ein einmaliger Befehl um (docs/deployment.md, „Medienordner“).
 */
final class Version20261126120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'BetAI: Logo-Pfade /assets/bet_ai/logos/ → /media/bet_ai/logos/';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE bet_ai_team SET profile_img_url = CONCAT('/media/bet_ai/logos/', SUBSTRING(profile_img_url, 22)) WHERE profile_img_url LIKE '/assets/bet_ai/logos/%'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE bet_ai_team SET profile_img_url = CONCAT('/assets/bet_ai/logos/', SUBSTRING(profile_img_url, 21)) WHERE profile_img_url LIKE '/media/bet_ai/logos/%'");
    }
}
