<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Renomme la valeur 'college' du champ user.typeuser en 'etablissement',
 * en cohérence avec le renommage ROLE_COLLEGE -> ROLE_ETABLISSEMENT.
 */
final class Version20260726090236 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Renomme la valeur typeuser 'college' en 'etablissement'";
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE user SET typeuser = 'etablissement' WHERE typeuser = 'college'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE user SET typeuser = 'college' WHERE typeuser = 'etablissement'");
    }
}
