<?php

namespace App\Repository\Webapp;

use App\Entity\Webapp\Page;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Page>
 */
class PageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Page::class);
    }

    public function ListMenu()
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.isMenu = :isMenu')
            ->andWhere("p.title != :home ")
            ->setParameter('isMenu', 1)
            ->setParameter('home', 'accueil')
            ->orderBy('p.id', 'ASC')
            ->getQuery()
            ->getResult()
            ;
    }

    /**
     * liste les pages par position ascendante
     */
    public function sortPosition(){
        return $this->createQueryBuilder('p')
            ->orderBy('p.position', 'ASC')
            ->getQuery()
            ->getResult()
            ;
    }
}
