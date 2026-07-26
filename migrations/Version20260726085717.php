<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Renomme College en Etablissement (table, colonnes, FK), crée le référentiel
 * TypeEtablissement (avec seed et rattachement des établissements existants
 * au type "Collège"), renomme le rôle ROLE_COLLEGE en ROLE_ETABLISSEMENT dans
 * les comptes utilisateurs existants, et renomme les constantes de contenu
 * de section ONE_COLLEGE/ALL_COLLEGES en ONE_ETABLISSEMENT/ALL_ETABLISSEMENTS.
 */
final class Version20260726085717 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Renomme College en Etablissement, ajoute TypeEtablissement, migre ROLE_COLLEGE en ROLE_ETABLISSEMENT';
    }

    public function up(Schema $schema): void
    {
        // 1. Renommer la table principale et ses colonnes spécifiques
        $this->addSql('RENAME TABLE college TO etablissement');
        $this->addSql('ALTER TABLE etablissement CHANGE college_email etablissement_email VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE etablissement CHANGE college_phone etablissement_phone VARCHAR(14) DEFAULT NULL');

        // 2. Table de référence TypeEtablissement + seed
        $this->addSql('CREATE TABLE type_etablissement (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(100) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql("INSERT INTO type_etablissement (id, libelle) VALUES (1, 'Collège'), (2, 'Centre de loisirs'), (3, 'Espace jeunes'), (4, 'Autre')");

        // 3. Colonne de rattachement sur etablissement, nullable le temps du backfill
        $this->addSql('ALTER TABLE etablissement ADD type_etablissement_id INT DEFAULT NULL');
        $this->addSql('UPDATE etablissement SET type_etablissement_id = 1');
        $this->addSql('ALTER TABLE etablissement CHANGE type_etablissement_id type_etablissement_id INT NOT NULL');
        $this->addSql('ALTER TABLE etablissement ADD CONSTRAINT FK_ETABLISSEMENT_TYPE_ETABLISSEMENT FOREIGN KEY (type_etablissement_id) REFERENCES type_etablissement (id)');
        $this->addSql('CREATE INDEX IDX_ETABLISSEMENT_TYPE_ETABLISSEMENT ON etablissement (type_etablissement_id)');

        // 4. Renommer les colonnes de FK dans les tables liées sans contrainte FK réelle en base
        $this->addSql('ALTER TABLE ressources CHANGE college_id etablissement_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE college_id etablissement_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE section CHANGE single_college_id single_etablissement_id INT DEFAULT NULL');

        // 5. article.college_id a une vraie contrainte FK -> la dropper avant de renommer la colonne
        $this->addSql('ALTER TABLE article DROP FOREIGN KEY FK_23A0E66770124B2');
        $this->addSql('ALTER TABLE article CHANGE college_id etablissement_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_ARTICLE_ETABLISSEMENT FOREIGN KEY (etablissement_id) REFERENCES etablissement (id)');

        // 6. Table de jointure ManyToMany
        $this->addSql('RENAME TABLE college_section TO etablissement_section');
        $this->addSql('ALTER TABLE etablissement_section CHANGE college_id etablissement_id INT NOT NULL');

        // 7. Migration des rôles JSON des comptes existants
        $this->addSql("UPDATE user SET roles = REPLACE(roles, 'ROLE_COLLEGE', 'ROLE_ETABLISSEMENT')");

        // 8. Renommage des valeurs de Section::content
        $this->addSql("UPDATE section SET content = 'ONE_ETABLISSEMENT' WHERE content = 'ONE_COLLEGE'");
        $this->addSql("UPDATE section SET content = 'ALL_ETABLISSEMENTS' WHERE content = 'ALL_COLLEGES'");
    }

    public function down(Schema $schema): void
    {
        // 8. Restaurer les valeurs de Section::content
        $this->addSql("UPDATE section SET content = 'ONE_COLLEGE' WHERE content = 'ONE_ETABLISSEMENT'");
        $this->addSql("UPDATE section SET content = 'ALL_COLLEGES' WHERE content = 'ALL_ETABLISSEMENTS'");

        // 7. Restaurer les rôles
        $this->addSql("UPDATE user SET roles = REPLACE(roles, 'ROLE_ETABLISSEMENT', 'ROLE_COLLEGE')");

        // 6. Table de jointure ManyToMany
        $this->addSql('ALTER TABLE etablissement_section CHANGE etablissement_id college_id INT NOT NULL');
        $this->addSql('RENAME TABLE etablissement_section TO college_section');

        // 5. article
        $this->addSql('ALTER TABLE article DROP FOREIGN KEY FK_ARTICLE_ETABLISSEMENT');
        $this->addSql('ALTER TABLE article CHANGE etablissement_id college_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_23A0E66770124B2 FOREIGN KEY (college_id) REFERENCES etablissement (id)');

        // 4. Colonnes FK des tables liées
        $this->addSql('ALTER TABLE ressources CHANGE etablissement_id college_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE etablissement_id college_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE section CHANGE single_etablissement_id single_college_id INT DEFAULT NULL');

        // 3. Colonne de rattachement TypeEtablissement
        $this->addSql('ALTER TABLE etablissement DROP FOREIGN KEY FK_ETABLISSEMENT_TYPE_ETABLISSEMENT');
        $this->addSql('DROP INDEX IDX_ETABLISSEMENT_TYPE_ETABLISSEMENT ON etablissement');
        $this->addSql('ALTER TABLE etablissement DROP type_etablissement_id');

        // 2. Table de référence
        $this->addSql('DROP TABLE type_etablissement');

        // 1. Table principale
        $this->addSql('ALTER TABLE etablissement CHANGE etablissement_email college_email VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE etablissement CHANGE etablissement_phone college_phone VARCHAR(14) DEFAULT NULL');
        $this->addSql('RENAME TABLE etablissement TO college');
    }
}
