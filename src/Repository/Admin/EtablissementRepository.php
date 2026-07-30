<?php

namespace App\Repository\Admin;

use App\Entity\Admin\Etablissement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Etablissement>
 */
class EtablissementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Etablissement::class);
    }

    public function listEtablissementsBySection($idsection)
    {
        return $this->createQueryBuilder('e')
            ->addSelect('e.id, e.name, e.city, e.isActive, e.logoName, s.id as idsection, t.id as idTypeEtablissement, t.libelle as typeEtablissementLibelle')
            ->leftJoin('e.section', 's')
            ->leftJoin('e.typeEtablissement', 't')
            ->andWhere('e.isActive = :isActive ')
            ->setParameter('isActive', 1)
            ->orderBy('t.libelle', 'ASC')
            ->addOrderBy('e.city', 'ASC')
            ->getQuery()
            ->getResult()
            ;
    }

    public function listEtablissementsByType($idTypeEtablissement)
    {
        return $this->createQueryBuilder('e')
            ->addSelect('e.id, e.name, e.city, e.isActive, e.logoName, t.id as idTypeEtablissement, t.libelle as typeEtablissementLibelle')
            ->leftJoin('e.typeEtablissement', 't')
            ->andWhere('e.isActive = :isActive')
            ->andWhere('t.id = :idTypeEtablissement')
            ->setParameter('isActive', 1)
            ->setParameter('idTypeEtablissement', $idTypeEtablissement)
            ->orderBy('e.city', 'ASC')
            ->getQuery()
            ->getResult()
            ;
    }

    public function EtablissementByUser($iduser)
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('e.user', 'u')
            ->andWhere('u.id = :iduser')
            ->setParameter('iduser', $iduser)
            ->orderBy('e.id', 'ASC')
            ->getQuery()
            ->getOneOrNullResult()
            ;
    }

    // /**
    //  * @return Etablissement[] Returns an array of Etablissement objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('e.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Etablissement
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
