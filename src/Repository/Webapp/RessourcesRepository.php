<?php

namespace App\Repository\Webapp;

use App\Entity\Gestapp\Ressources;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ressources>
 */
class RessourcesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ressources::class);
    }

    public function ListByCategory($ressourcecat): ?Ressources
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.category', 'rc')
            ->andWhere('rc.id = :category')
            ->setParameter('category', $ressourcecat)
            ->getQuery()
            ->getResult()
            ;
    }

    public function ListFilters($filters)
    {
        $query = $this->createQueryBuilder('r');
        if($filters != null){
            $query->andWhere('r.category IN(:cats)')
                ->setParameter(':cats', array_values($filters));
        }
        return $query->getQuery()->getResult();
    }

    /**
     * @param $slug
     * @return int|mixed|string|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     * Affiche une ressource selon son slug
     */
    public function ressourceSlug($slug)
    {
        return $this->createQueryBuilder('r')
            ->addSelect('
                r.id as id,
                r.slug,
                r.name as name,
                r.content as content,
                r.doc,
                r.imageName,
                r.isTitleShow,
                r.isShowIntro,
                r.Linkmedia,
                su.id AS support,
                su.name AS supportName
                ')
            ->leftJoin('r.support', 'su')
            ->andWhere('r.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult()
            ;
    }

    // /**
    //  * @return Ressources[] Returns an array of Ressources objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('r.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Ressources
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
