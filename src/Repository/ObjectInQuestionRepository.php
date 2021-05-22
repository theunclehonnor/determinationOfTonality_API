<?php

namespace App\Repository;

use App\Entity\ObjectInQuestion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ObjectInQuestion|null find($id, $lockMode = null, $lockVersion = null)
 * @method ObjectInQuestion|null findOneBy(array $criteria, array $orderBy = null)
 * @method ObjectInQuestion[]    findAll()
 * @method ObjectInQuestion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ObjectInQuestionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ObjectInQuestion::class);
    }

    // /**
    //  * @return ObjectInQuestion[] Returns an array of ObjectInQuestion objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('o.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ObjectInQuestion
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
