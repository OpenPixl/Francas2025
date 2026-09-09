<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajoute page.image : nom de fichier de l'image d'illustration d'une page
 * (affichée dans le hero d'introduction). Fichiers stockés dans
 * public/uploads/images/pages/.
 */
final class Version20260909140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Page: ajout de la colonne image (illustration)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE page ADD image VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE page DROP image');
    }
}
