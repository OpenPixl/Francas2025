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

    public function listArticlesBySection($idsection)
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.sections', 's')
            ->leftJoin('a.college', 'c')
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
                t.id as idtheme,
                t.name as theme,
                a.imageName,
                a.createdAt,
                a.doc as doc,
                su.id as idsupport,
                su.name as support,
                c.id AS idcollege
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

    public function listArticlesByColleges($idsection)
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.section', 's')
            ->andWhere('a.college > 0')
            ->andWhere('a.isArchived = :isArchived')
            ->setParameter('isArchived', 0)
            ->orderBy('a.id', 'ASC')
            ->getQuery()
            ->getResult()
            ;
    }

    public function listArticlesByCollege($idcollege)
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
                c.id AS idCollege,
                c.name AS nameCollege,
                c.animateur As animateur,
                c.logoName As logoNameCollege
                ')
            ->leftJoin('a.college', 'c')
            ->leftJoin('a.theme', 't')
            ->leftJoin('a.support' , 's')
            ->andWhere('c.id = :idcollege')
            ->setParameter('idcollege', $idcollege)
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
            ->leftJoin('a.college', 'c')
            ->leftJoin('a.author', 'u')
            ->leftJoin('a.theme', 't')
            ->addSelect('
                a.id as id,
                a.slug as slug,
                a.title as title,
                a.content as content,
                a.imageName as imageName,
                a.updatedAt as updatedAt,
                c.id AS idcollege,
                c.logoName AS logoName,
                u.typeuser as typeuser,
                t.name as theme
                 ')
            ->where('u.typeuser = :typeuser')
            ->setParameter('typeuser', 'college')
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
    public function articlecollegeSlug($id)
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
                c.name, c.id AS idcollege,
                c.headerName,
                c.logoName,
                c.GroupDescription,
                a.isShowReadMore,
                s.id as idsupport,
                s.name as support
                 ')
            ->leftJoin('a.college', 'c')
            ->leftJoin('a.theme', 't')
            ->leftJoin('a.support' , 's')
            ->leftJoin('a.category', 'ca')
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
