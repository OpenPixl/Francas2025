<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Rend config.vignette_name nullable : la suppression du média « vignette » de la
 * page Paramètres remet ce champ à NULL (même principe que header_name, déjà
 * nullable). Voir ConfigController::deleteMedia().
 */
final class Version20260909130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Config: vignette_name devient nullable (suppression de média)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE config CHANGE vignette_name vignette_name VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE config SET vignette_name = '' WHERE vignette_name IS NULL");
        $this->addSql('ALTER TABLE config CHANGE vignette_name vignette_name VARCHAR(255) NOT NULL');
    }
}
