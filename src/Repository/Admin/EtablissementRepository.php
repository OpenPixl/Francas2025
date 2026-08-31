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

    /**
     * Recharge des établissements avec leur type en une seule requête, à partir
     * d'une liste d'IDs (typiquement issus d'une recherche Elasticsearch).
     * Évite le N+1 sur `getTypeEtablissement()` lors de l'itération. L'ordre des
     * IDs est conservé.
     *
     * @param int[] $ids
     * @return Etablissement[] indexé par id, dans l'ordre de $ids
     */
    public function findWithTypeByIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter($ids)));
        if (!$ids) {
            return [];
        }

        $rows = $this->createQueryBuilder('e')
            ->addSelect('t')
            ->leftJoin('e.typeEtablissement', 't')
            ->andWhere('e.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();

        $byId = [];
        foreach ($rows as $etablissement) {
            $byId[$etablissement->getId()] = $etablissement;
        }

        $ordered = [];
        foreach ($ids as $id) {
            if (isset($byId[$id])) {
                $ordered[$id] = $byId[$id];
            }
        }

        return $ordered;
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
