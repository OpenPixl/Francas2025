<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Page : zone paramétrable sous la navbar.
 *  - under_nav_hidden : NULL/1 = ne rien afficher ; 0 = afficher ;
 *  - under_nav_type    : 'player' (lecteur des 5 dernières publications audio)
 *                        ou 'banner' (bannière du site).
 *
 * Colonne nullable sans défaut SQL : les pages existantes restent « rien »
 * (NULL est traité comme masqué côté template), les nouvelles pages prennent
 * la valeur par défaut de l'entité (true).
 */
final class Version20260910120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Page: colonnes under_nav_hidden / under_nav_type (zone sous la navbar)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE page ADD under_nav_hidden TINYINT(1) DEFAULT NULL, ADD under_nav_type VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE page DROP under_nav_hidden, DROP under_nav_type');
    }
}
