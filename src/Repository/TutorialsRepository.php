<?php

namespace App\Repository;

use App\Entity\Tutorials;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tutorials>
 *
 * @method Tutorials|null find($id, $lockMode = null, $lockVersion = null)
 * @method Tutorials|null findOneBy(array $criteria, array $orderBy = null)
 * @method Tutorials[]    findAll()
 * @method Tutorials[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TutorialsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tutorials::class);
    }

    public function getTutorialsWithGames()
    {
        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.game', 'g')
            ->addSelect('g');

        return $qb->getQuery();
    }

    public function save(Tutorials $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Tutorials $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return Tutorials[] Returns an array of Tutorials objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('t.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Tutorials
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
