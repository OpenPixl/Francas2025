<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Renomme les index issus du renommage college -> etablissement pour suivre
 * la convention de nommage Doctrine (cosmétique, aucune donnée affectée).
 * Les autres écarts de schéma révélés par doctrine:schema:validate sont
 * préexistants au renommage (FK jamais créées en base sur plusieurs relations
 * hors périmètre de ce changement) et ne sont pas traités ici.
 */
final class Version20260726085952 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Renomme les index liés à etablissement pour suivre la convention Doctrine';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE article RENAME INDEX idx_23a0e66770124b2 TO IDX_23A0E66FF631228');
        $this->addSql('ALTER TABLE etablissement RENAME INDEX idx_etablissement_type_etablissement TO IDX_20FD592CA8FC9399');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE article RENAME INDEX idx_23a0e66ff631228 TO IDX_23A0E66770124B2');
        $this->addSql('ALTER TABLE etablissement RENAME INDEX idx_20fd592ca8fc9399 TO IDX_ETABLISSEMENT_TYPE_ETABLISSEMENT');
    }
}
