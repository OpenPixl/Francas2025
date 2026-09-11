<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Theme : passage de theme.name de VARCHAR(20) à VARCHAR(50).
 *
 * Élargissement simple, non destructif (aucune donnée existante ne dépasse 20).
 */
final class Version20260910170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Theme: theme.name VARCHAR(20) -> VARCHAR(50)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE theme CHANGE name name VARCHAR(50) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE theme CHANGE name name VARCHAR(20) NOT NULL');
    }
}
