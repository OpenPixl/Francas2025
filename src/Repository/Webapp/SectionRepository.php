<?php

namespace App\Repository\Webapp;

use App\Entity\Webapp\Section;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Section>
 */
class SectionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Section::class);
    }

    public function findbypage($page){
        return $this->createQueryBuilder('s')
            ->select('
                s.id,
                s.name,
                s.slug,
                s.descriptif,
                s.favorites,
                s.content,
                s.createdAt,
                s.updatedAt,
                s.position,
                p.id AS ispage')
            ->join('s.page', 'p')
            ->Where('p.id = :page')
            ->setParameter('page', $page)
            ->orderBy('s.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // /**
    //  * @return Section[] Returns an array of Section objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Section
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function ListAllSections($page)
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.page', 'p')
            ->andWhere('p.id = :page')
            ->andWhere('s.isActiv = :isActiv')
            ->setParameter('page', $page)
            ->setParameter('isActiv', 1)
            ->orderBy('s.position', 'ASC')
            ->getQuery()
            ->getResult()
            ;
    }
}
