<?php

namespace App\Repository;

use App\Entity\Leads;
use App\Entity\User;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Leads>
 *
 * @method Leads|null find($id, $lockMode = null, $lockVersion = null)
 * @method Leads|null findOneBy(array $criteria, array $orderBy = null)
 * @method Leads[]    findAll()
 * @method Leads[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LeadsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Leads::class);
    }

    public function getLeads($user, DateTime $from, DateTime $to)
    {
        $qb = $this->createQueryBuilder('l');

        $qb->select('l')
            ->where('l.buyer = :user')
            ->andWhere('l.createAt >= :from')
            ->andWhere('l.createAt <= :to')
            ->setParameter('user', $user)
            ->setParameter('from', $from)
            ->setParameter('to', $to);

        return $qb->getQuery()->getResult();
    }

    public function getGroupLeads($from = null, $to = null, User $user = null)
    {
        $qb = $this->createQueryBuilder('l');

        $qb->select('u.telegram', 'u.rate', 'u.id', 'COUNT(l.id) as leads', 'SUM(l.payout) as payout', 'l.status')
        ->leftJoin('l.buyer', 'u')
        ->groupBy('u.telegram')
        ->addGroupBy('l.status');

        if ($user) {
            $qb->where('u.telegram = :user')
                ->setParameter('user', $user->getId());
        }
        if ($from) {
            $qb->andWhere('l.createAt > :from')
                ->setParameter('from', $from);
        }
        if ($to) {
            $qb->andWhere('l.createAt <= :to')
                ->setParameter('to', $to);
        }

        return $qb->getQuery()->getResult();
    }

    public function save(Leads $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Leads $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return Leads[] Returns an array of Leads objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('l')
//            ->andWhere('l.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('l.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Leads
//    {
//        return $this->createQueryBuilder('l')
//            ->andWhere('l.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
