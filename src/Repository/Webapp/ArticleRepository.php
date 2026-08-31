<?php

namespace App\Repository\Webapp;

use App\Entity\Webapp\Article;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Article>
 */
class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    /**
     * Recharge en une seule requête des articles (avec les relations utilisées
     * à l'affichage : établissement, type d'établissement, thème, support) à
     * partir d'une liste d'IDs — typiquement ceux renvoyés par une recherche
     * Elasticsearch. Évite le N+1 provoqué par l'itération sur les entités
     * hydratées par le finder. L'ordre des IDs (pertinence ES) est conservé.
     *
     * @param int[] $ids
     * @return Article[] indexé par id, dans l'ordre de $ids
     */
    public function findWithRelationsByIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter($ids)));
        if (!$ids) {
            return [];
        }

        $rows = $this->createQueryBuilder('a')
            ->addSelect('e', 'te', 't', 'su')
            ->leftJoin('a.etablissement', 'e')
            ->leftJoin('e.typeEtablissement', 'te')
            ->leftJoin('a.theme', 't')
            ->leftJoin('a.support', 'su')
            ->andWhere('a.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();

        $byId = [];
        foreach ($rows as $article) {
            $byId[$article->getId()] = $article;
        }

        $ordered = [];
        foreach ($ids as $id) {
            if (isset($byId[$id])) {
                $ordered[$id] = $byId[$id];
            }
        }

        return $ordered;
    }

    public function allArticles(){
        return $this->createQueryBuilder('a')
            ->leftJoin('a.etablissement', 'e')
            ->leftJoin('e.typeEtablissement', 'te')
            ->leftJoin('a.theme', 't')
            ->leftJoin('a.support' , 'su')
            ->addSelect('
                a.id as id,
                a.slug as slug,
                a.title as title,
                a.isTitleShow,
                a.isShowReadMore,
                a.content as content,
                a.isArchived as isArchived,
                a.isShowCreated as isShowCreated,
                a.updatedAt as updatedAt,
                a.doc as doc,
                t.id as idtheme,
                t.name as theme,
                a.imageName,
                su.id as idSupport,
                su.name as support,
                e.id AS idEtablissement,
                e.name as nameEtablissement,
                e.logoName as logoEtablissement,
                te.id as idTypeEtablissement,
                te.libelle as libelleEtablissement
                '
            )
            ->andWhere('a.isArchived = :isArchived')
            ->andWhere('a.etablissement is not null')
            ->setParameter('isArchived', 0)
            ->orderBy('a.updatedAt', 'DESC')
            ->getQuery()
            ->getResult()
            ;
    }

    public function listArticlesBySection($idsection)
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.sections', 's')
            ->leftJoin('a.etablissement', 'e')
            ->leftJoin('a.theme', 't')
            ->leftJoin('a.support' , 'su')
            ->leftJoin('a.author', 'au')
            ->addSelect('
                a.id as id,
                a.slug as slug,
                a.title as title,
                a.isTitleShow,
                a.isShowReadMore,
                a.content as content,
                a.isArchived as isArchived,
                a.isShowCreated as isShowCreated,
                t.id as idtheme,
                t.name as theme,
                a.imageName,
                a.createdAt,
                a.doc as doc,
                su.id as idsupport,
                su.name as support,
                e.id AS idetablissement,
                au.id as idauthor
                '
            )
            ->andWhere('s.id = :idsection')
            ->andWhere('a.isArchived = :isArchived')
            ->setParameter('idsection', $idsection)
            ->setParameter('isArchived', 0)
            ->orderBy('a.id', 'ASC')
            ->getQuery()
            ->getOneOrNullResult()
            ;
    }

    public function listArticlesByEtablissements($idsection)
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.section', 's')
            ->andWhere('a.etablissement > 0')
            ->andWhere('a.isArchived = :isArchived')
            ->setParameter('isArchived', 0)
            ->orderBy('a.id', 'ASC')
            ->getQuery()
            ->getResult()
            ;
    }

    public function listArticlesByEtablissement($idetablissement)
    {
        return $this->createQueryBuilder('a')
            ->addSelect('
                a.id as id,
                a.slug,
                a.title as title,
                a.content as content,
                t.id as idtheme,
                t.name as theme,
                a.imageName,
                a.createdAt,
                a.updatedAt,
                s.id as idsupport,
                s.name as support,
                e.id AS idEtablissement,
                e.name AS nameEtablissement,
                e.animateur As animateur,
                e.logoName As logoNameEtablissement
                ')
            ->leftJoin('a.etablissement', 'e')
            ->leftJoin('a.theme', 't')
            ->leftJoin('a.support' , 's')
            ->andWhere('e.id = :idetablissement')
            ->setParameter('idetablissement', $idetablissement)
            ->andWhere('a.isArchived = :isArchived')
            ->setParameter('isArchived', 0)
            ->orderBy('a.updatedAt', 'DESC')
            ->getQuery()
            ->getResult()
            ;
    }

    public function listFiveArticles($category)
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.etablissement', 'e')
            ->leftJoin('a.author', 'u')
            ->leftJoin('a.theme', 't')
            ->addSelect('
                a.id as id,
                a.slug as slug,
                a.title as title,
                a.content as content,
                a.imageName as imageName,
                a.updatedAt as updatedAt,
                e.id AS idetablissement,
                e.logoName AS logoName,
                u.typeuser as typeuser,
                u.id as idauthor,
                t.name as theme
                 ')
            ->where('u.typeuser = :typeuser')
            ->setParameter('typeuser', 'etablissement')
            ->orderBy('a.updatedAt', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult()
            ;
    }

    /**
     * @param $slug
     * @return int|mixed|string|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     * Affiche un articel selon son slug
     */
    public function articleEtablissementSlug($id)
    {
        return $this->createQueryBuilder('a')
            ->addSelect('
                a.id as id,
                a.slug,
                a.title as title,
                a.content as content,
                a.doc,
                a.isArchived as isArchived,
                a.isShowCreated as isShowCreated,
                t.id as idtheme,
                t.name as theme,
                a.imageName,
                a.isTitleShow,
                a.intro,
                a.isShowIntro,
                a.createdAt As createdAt,
                e.name, e.id AS idetablissement,
                e.headerName,
                e.logoName,
                e.GroupDescription,
                a.isShowReadMore,
                s.id as idsupport,
                s.name as support,
                au.id as idauthor
                 ')
            ->leftJoin('a.etablissement', 'e')
            ->leftJoin('a.theme', 't')
            ->leftJoin('a.support' , 's')
            ->leftJoin('a.category', 'ca')
            ->leftJoin('a.author', 'au')
            ->andWhere('a.isArchived = :isArchived')
            ->setParameter('isArchived', 0)
            ->andWhere('a.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult()
            ;
    }

    /**
     * Recherche les articles a partir du moteur de recherche
     * @return void
     */
    public function searchArticles($title = null, $author = null){
        $query = $this->createQueryBuilder("a");
        if($title != null){
            $query
                ->andWhere('MATCH_AGAINST(a.title) AGAINST (:title boolean)>0')
                ->setParameter('title', $title);
        }
        if($author !=null){
            $query->join('a.author', 'u');
            $query->andWhere('u.id = :id')
                ->setParameter('id', $author);
        }
        return $query->getQuery()->getResult();
    }
}
