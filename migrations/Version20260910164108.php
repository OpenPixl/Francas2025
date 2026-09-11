<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Section : colonne type_etablissement_id (contenu « un type d'établissement »).
 *
 * Nouveau type de contenu de section `ONE_TYPE_ETABLISSEMENT` : la section
 * affiche un bouton « Voir les {libellé} » pointant vers la page dédiée du
 * type d'établissement (route `op_webapp_etablissement_bytype`).
 *
 * FK nullable : les sections existantes ne référencent aucun type.
 */
final class Version20260910164108 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Section: colonne type_etablissement_id (contenu "un type d\'établissement")';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE section ADD type_etablissement_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE section ADD CONSTRAINT FK_2D737AEFA8FC9399 FOREIGN KEY (type_etablissement_id) REFERENCES type_etablissement (id)');
        $this->addSql('CREATE INDEX IDX_2D737AEFA8FC9399 ON section (type_etablissement_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE section DROP FOREIGN KEY FK_2D737AEFA8FC9399');
        $this->addSql('DROP INDEX IDX_2D737AEFA8FC9399 ON section');
        $this->addSql('ALTER TABLE section DROP type_etablissement_id');
    }
}
