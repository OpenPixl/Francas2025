<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Passe le thème d'un article d'une relation unique (article.theme_id, ManyToOne)
 * à une relation multiple (table de jointure article_theme, ManyToMany).
 *
 * Les thèmes déjà affectés sont repris dans la table de jointure avant la
 * suppression de la colonne article.theme_id.
 */
final class Version20260909120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Article: theme (ManyToOne) -> themes (ManyToMany via article_theme)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE article_theme (article_id INT NOT NULL, theme_id INT NOT NULL, INDEX IDX_E0C295197294869C (article_id), INDEX IDX_E0C2951959027487 (theme_id), PRIMARY KEY(article_id, theme_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE article_theme ADD CONSTRAINT FK_E0C295197294869C FOREIGN KEY (article_id) REFERENCES article (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE article_theme ADD CONSTRAINT FK_E0C2951959027487 FOREIGN KEY (theme_id) REFERENCES theme (id) ON DELETE CASCADE');

        // Reprise des affectations existantes avant suppression de la colonne.
        $this->addSql('INSERT INTO article_theme (article_id, theme_id) SELECT id, theme_id FROM article WHERE theme_id IS NOT NULL');

        $this->addSql('ALTER TABLE article DROP FOREIGN KEY FK_23A0E6659027487');
        $this->addSql('DROP INDEX IDX_23A0E6659027487 ON article');
        $this->addSql('ALTER TABLE article DROP theme_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE article ADD theme_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_23A0E6659027487 FOREIGN KEY (theme_id) REFERENCES theme (id)');
        $this->addSql('CREATE INDEX IDX_23A0E6659027487 ON article (theme_id)');

        // On ne peut garder qu'un seul thème par article : on reprend le plus petit id.
        $this->addSql('UPDATE article a SET a.theme_id = (SELECT MIN(at.theme_id) FROM article_theme at WHERE at.article_id = a.id)');

        $this->addSql('ALTER TABLE article_theme DROP FOREIGN KEY FK_E0C295197294869C');
        $this->addSql('ALTER TABLE article_theme DROP FOREIGN KEY FK_E0C2951959027487');
        $this->addSql('DROP TABLE article_theme');
    }
}
