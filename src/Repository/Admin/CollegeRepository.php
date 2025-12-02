<?php

namespace App\Repository\Admin;

use App\Entity\Admin\College;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<College>
 */
class CollegeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, College::class);
    }

    public function listCollegesBySection($idsection)
    {
        return $this->createQueryBuilder('c')
            ->addSelect('c.id, c.name, c.city, c.isActive, c.logoName, s.id as idsection')
            ->leftJoin('c.section', 's')
            ->andWhere('c.isActive = :isActive ')
            ->setParameter('isActive', 1)
            ->orderBy('c.city', 'ASC')
            ->getQuery()
            ->getResult()
            ;
    }

    public function CollegeByUser($iduser)
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.user', 'u')
            ->andWhere('u.id = :iduser')
            ->setParameter('iduser', $iduser)
            ->orderBy('c.id', 'ASC')
            ->getQuery()
            ->getOneOrNullResult()
            ;
    }

    // /**
    //  * @return College[] Returns an array of College objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?College
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
